<?php

namespace App\Http\Controllers;

use App\Models\HistoricalBoardExamResult;
use App\Models\MockBoard;
use Illuminate\Http\Request;

/**
 * Manual CRUD for real-world board/licensure exam results, and the endpoint
 * a teacher uses to link one of these records to a mock board for
 * comparison. Per product decision, entering/editing historical records is
 * admin/superadmin-gated (it becomes shared reference data used across
 * comparisons), while any teacher may VIEW the list and link their own
 * mock board to a record.
 */
class HistoricalBoardExamController extends Controller
{
    /**
     * List historical exam results, optionally filtered by program.
     * Available to any authenticated teacher/admin/superadmin (read-only
     * for teachers) so they can pick one to link on their mock board.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! in_array($user?->role, ['teacher', 'admin', 'superadmin'], true)) {
            abort(403);
        }

        $query = HistoricalBoardExamResult::query()->orderBy('exam_period_or_year', 'desc');

        if ($program = $request->query('program')) {
            $query->where('program', $program);
        }

        $results = $query->get()->map(fn (HistoricalBoardExamResult $r) => [
            'id' => $r->id,
            'program' => $r->program,
            'exam_label' => $r->exam_label,
            'exam_period_or_year' => $r->exam_period_or_year,
            'total_examinees' => $r->total_examinees,
            'passed_count' => $r->passed_count,
            'passing_rate' => $r->passing_rate,
            'source_note' => $r->source_note,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['results' => $results]);
        }

        return view('pages.admin.historical-board-exams.index', [
            'results' => $results,
        ]);
    }

    /**
     * Create a new historical exam record. Admin/superadmin only.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'program' => 'required|in:psychology,education,accountancy',
            'exam_label' => 'required|string|max:255',
            'exam_period_or_year' => 'required|string|max:50',
            'total_examinees' => 'required|integer|min:1',
            'passed_count' => 'required|integer|min:0|lte:total_examinees',
            'source_note' => 'nullable|string|max:1000',
        ]);

        $result = HistoricalBoardExamResult::create($validated + [
            'entered_by' => $request->user()->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Historical exam result created.', 'result' => $result], 201);
        }

        return redirect()->back()->with('success', 'Historical exam result added.');
    }

    /**
     * Update an existing historical exam record. Admin/superadmin only.
     */
    public function update(Request $request, HistoricalBoardExamResult $historicalBoardExamResult)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'program' => 'sometimes|in:psychology,education,accountancy',
            'exam_label' => 'sometimes|string|max:255',
            'exam_period_or_year' => 'sometimes|string|max:50',
            'total_examinees' => 'sometimes|integer|min:1',
            'passed_count' => 'sometimes|integer|min:0|lte:total_examinees',
            'source_note' => 'nullable|string|max:1000',
        ]);

        $historicalBoardExamResult->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Historical exam result updated.', 'result' => $historicalBoardExamResult->fresh()]);
        }

        return redirect()->back()->with('success', 'Historical exam result updated.');
    }

    /**
     * Delete a historical exam record. Admin/superadmin only.
     */
    public function destroy(Request $request, HistoricalBoardExamResult $historicalBoardExamResult)
    {
        $this->authorizeAdmin($request);

        $historicalBoardExamResult->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Historical exam result deleted.']);
        }

        return redirect()->back()->with('success', 'Historical exam result deleted.');
    }

    /**
     * Teacher: link (or unlink) their own mock board to a historical exam
     * record for comparison. Manual link only — the teacher picks which
     * real exam is the right comparison point, no automatic matching.
     */
    public function link(Request $request, MockBoard $mockBoard)
    {
        $user = $request->user();

        if ($mockBoard->teacher_id !== $user->id && ! in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403, 'You do not have permission to modify this Mock Board.');
        }

        $validated = $request->validate([
            'historical_board_exam_result_id' => 'nullable|exists:historical_board_exam_results,id',
        ]);

        if (! empty($validated['historical_board_exam_result_id'])) {
            $historical = HistoricalBoardExamResult::findOrFail($validated['historical_board_exam_result_id']);

            if (strtolower($historical->program) !== strtolower($mockBoard->program)) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => 'That historical exam record is for a different program.'], 422);
                }

                return redirect()->back()->with('error', 'That historical exam record is for a different program.');
            }
        }

        $mockBoard->update(['historical_board_exam_result_id' => $validated['historical_board_exam_result_id'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Mock board comparison link updated.', 'mock_board' => $mockBoard->fresh()]);
        }

        return redirect()->back()->with('success', 'Comparison link updated.');
    }

    /**
     * Teacher/Admin: Quick type down a previous physical copy passing rate and immediately link it to this mock board.
     */
    public function quickBenchmark(Request $request, MockBoard $mockBoard)
    {
        $user = $request->user();

        if ($mockBoard->teacher_id !== $user->id && ! in_array($user->role, ['admin', 'superadmin'], true)) {
            abort(403, 'You do not have permission to modify this Mock Board.');
        }

        // Option 1: Existing record selected
        if ($request->filled('historical_board_exam_result_id')) {
            return $this->link($request, $mockBoard);
        }

        // Option 2: Typing in a new previous physical exam passing rate
        $validated = $request->validate([
            'exam_label' => 'required|string|max:255',
            'exam_period_or_year' => 'nullable|string|max:50',
            'passing_rate' => 'required|numeric|min:0|max:100',
            'total_examinees' => 'nullable|integer|min:1',
            'passed_count' => 'nullable|integer|min:0',
            'source_note' => 'nullable|string|max:1000',
        ]);

        $total = ! empty($validated['total_examinees']) ? (int) $validated['total_examinees'] : 100;
        $passed = ! empty($validated['passed_count'])
            ? (int) $validated['passed_count']
            : (int) round(($validated['passing_rate'] / 100) * $total);

        if ($passed > $total) {
            $passed = $total;
        }

        $program = strtolower($mockBoard->program);
        if (! in_array($program, ['psychology', 'education', 'accountancy'], true)) {
            $program = 'accountancy';
        }

        $record = HistoricalBoardExamResult::create([
            'program' => $program,
            'exam_label' => $validated['exam_label'],
            'exam_period_or_year' => ! empty($validated['exam_period_or_year']) ? $validated['exam_period_or_year'] : date('Y'),
            'total_examinees' => $total,
            'passed_count' => $passed,
            'source_note' => ! empty($validated['source_note']) ? $validated['source_note'] : 'Typed from physical PRC copy',
            'entered_by' => $user->id,
        ]);

        $mockBoard->update(['historical_board_exam_result_id' => $record->id]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Physical board exam benchmark saved and linked!', 'result' => $record]);
        }

        return redirect()->back()->with('success', 'Physical board exam benchmark saved and linked!');
    }

    private function authorizeAdmin(Request $request): void
    {
        if (! in_array($request->user()?->role, ['admin', 'superadmin'], true)) {
            abort(403, 'Only admins can manage historical board exam results.');
        }
    }
}
