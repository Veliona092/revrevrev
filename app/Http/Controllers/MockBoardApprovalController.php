<?php

namespace App\Http\Controllers;

use App\Models\MockBoard;
use Illuminate\Http\Request;

class MockBoardApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                abort(403, 'Admins only.');
            }

            return $next($request);
        });
    }

    /**
     * Admin: listahan ng mock boards na naghihintay ng approval (o filtered by status).
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = MockBoard::with(['teacher', 'phases.module.quizQuestions'])
            ->orderBy('created_at', 'desc');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $mockBoards = $query->get();

        return view('pages.admin.mock-boards.approvals', [
            'mockBoards' => $mockBoards,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Admin: i-approve ang mock board.
     */
    public function approve(MockBoard $mockBoard)
    {
        $mockBoard->markApproved(auth()->user());

        return redirect()->back()->with('success', "\"{$mockBoard->title}\" has been approved and is now visible to students.");
    }

    /**
     * Admin: i-reject ang mock board, na may optional na dahilan.
     */
    public function reject(Request $request, MockBoard $mockBoard)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $mockBoard->markRejected(auth()->user(), $validated['rejection_reason'] ?? null);

        return redirect()->back()->with('success', "\"{$mockBoard->title}\" has been rejected.");
    }

    /**
     * Admin: Kumuha ng buong detalye at mga tanong ng mock board para sa modal preview.
     */
    public function questions(MockBoard $mockBoard)
    {
        $mockBoard->loadMissing([
            'teacher',
            'phases.module.quizQuestions',
        ]);

        $phasesData = $mockBoard->phases->map(function ($phase) {
            $questions = $phase->module?->quizQuestions ?? collect();

            return [
                'id' => $phase->id,
                'phase_type' => $phase->phase_type,
                'label' => $phase->title ?: ($phase->phase_label ?: ucfirst(str_replace('_', ' ', $phase->phase_type))),
                'sequence_number' => $phase->sequence_number,
                'time_limit' => $phase->module?->time_limit ?? 0,
                'passing_grade' => $phase->module?->passing_grade ?? 75,
                'total_questions' => $questions->count(),
                'questions' => $questions->map(function ($q, $idx) {
                    $options = is_array($q->options) ? $q->options : (json_decode($q->options, true) ?? []);

                    return [
                        'id' => $q->id,
                        'order' => $q->order ?? ($idx + 1),
                        'question_text' => $q->question_text,
                        'options' => $options,
                        'correct_option' => $q->correct_option,
                        'points' => $q->points ?? 1,
                        'domain' => $q->domain ?? null,
                        'difficulty' => $q->difficulty ?? null,
                        'explanation' => $q->explanation ?? null,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'mock_board' => [
                'id' => $mockBoard->id,
                'title' => $mockBoard->title,
                'description' => $mockBoard->description,
                'program' => $mockBoard->program,
                'teacher' => [
                    'name' => $mockBoard->teacher?->name ?? 'Unknown Teacher',
                    'email' => $mockBoard->teacher?->email ?? '',
                    'idnumber' => $mockBoard->teacher?->idnumber ?? '',
                ],
                'status' => $mockBoard->status,
                'passing_percentage' => $mockBoard->passing_percentage ?? 75,
                'review_period' => ($mockBoard->review_period_start && $mockBoard->review_period_end)
                    ? $mockBoard->review_period_start->format('M d, Y').' - '.$mockBoard->review_period_end->format('M d, Y')
                    : null,
                'phases' => $phasesData,
            ],
        ]);
    }
}
