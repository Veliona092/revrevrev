<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\Question; // Idinagdag para sa approveGeneratedQuestions
use App\Models\User;
use App\Services\MockBoardStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MockBoardController extends Controller
{
    public function __construct(
        private MockBoardStatisticsService $statisticsService
    ) {}

    /**
     * Admin: List all mock boards for management.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Check kung Admin ang logged-in user
        $isAdmin = $user->role === 'admin'
            || ($user->is_admin ?? false)
            || (method_exists($user, 'hasRole') && $user->hasRole('admin'));

        $teacherClasses = collect();
        $selectedClass = null;
        $mockBoards = collect();
        // Siguraduhing lowercase ang program para mag-match sa database
        $selectedProgram = strtolower(trim($request->query('program', '')));

        if ($isAdmin) {
            // KUNG ADMIN: Ipakita ang lahat ng Mock Boards (o i-filter batay sa program)
            $query = MockBoard::with(['phases.module.quizQuestions'])->withCount(['attempts']);

            if (! empty($selectedProgram)) {
                $query->whereRaw('LOWER(program) = ?', [$selectedProgram]);
            }

            $mockBoards = $query->orderBy('created_at', 'desc')->get();

        } else {
            // KUNG TEACHER: Hinahanap sa 'teacher_id', 'created_by', o 'user_id'
            $teacherClasses = ClassModel::where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhere('user_id', $user->id);
            })
                ->withCount('students')
                ->get();

            // Kunin ang class_id mula sa URL string, o kunin ang unang klase ng teacher kung wala pa
            $classId = $request->query('class_id') ?? ($teacherClasses->first()->id ?? null);

            if ($classId) {
                $selectedClass = $teacherClasses->firstWhere('id', $classId);

                if ($selectedClass) {
                    $selectedProgram = strtolower(trim($selectedClass->program ?? $selectedProgram));

                    // Kuhanin ang Mock Boards na nakatali sa class_id O kaya sa kaparehong program ng klase
                    // (per-teacher: dapat gawa mismo ng kasalukuyang teacher)
                    $mockBoards = MockBoard::where('teacher_id', $user->id)
                        ->where(function ($q) use ($selectedClass) {
                            $q->where('class_id', $selectedClass->id);

                            if (! empty($selectedClass->program)) {
                                $prog = strtolower(trim($selectedClass->program));
                                $q->orWhereRaw('LOWER(program) = ?', [$prog]);
                            }
                        })
                        ->with(['phases.module.quizQuestions'])
                        ->withCount(['attempts'])
                        ->orderBy('created_at', 'desc')
                        ->get();
                }
            } else {
                // FALLBACK KUNG WALANG MAHANAP NA KLASE:
                if (! empty($user->program)) {
                    $selectedProgram = strtolower(trim($user->program));
                    $mockBoards = MockBoard::where('teacher_id', $user->id)
                        ->whereRaw('LOWER(program) = ?', [$selectedProgram])
                        ->with(['phases.module.quizQuestions'])
                        ->withCount(['attempts'])
                        ->orderBy('created_at', 'desc')
                        ->get();
                }
            }
        }

        // Kinukuha at gi-ngroup ang mock boards para sa Blade view fallback
        // (admin: lahat; teacher: sarili lang niyang mga board)
        $mockBoardsByProgramQuery = MockBoard::with(['phases.module.quizQuestions'])
            ->withCount(['attempts']);

        if (! $isAdmin) {
            $mockBoardsByProgramQuery->where('teacher_id', $user->id);
        }

        $mockBoardsByProgram = $mockBoardsByProgramQuery
            ->get()
            ->groupBy(function ($item) {
                return strtolower(trim($item->program));
            });

        return view('pages.admin.mock-boards.index', [
            'teacherClasses' => $teacherClasses,
            'selectedClass' => $selectedClass,
            'mockBoards' => $mockBoards,
            'mock_boards_by_program' => $mockBoardsByProgram, // <-- NAKAPASOK NA ANG VARIABLE DITO
            'selectedProgram' => $selectedProgram,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * List mock boards filtered specifically by the teacher's assigned program.
     */
    public function batchAnalytics(Request $request)
    {
        $teacher = auth()->user();

        // Kunin ang program mula sa URL query string (hal. ?program=psychology),
        // kung wala, gamitin ang program ng teacher, o fallback sa 'education'
        $selectedProgram = $request->query('program', $teacher->program ?? 'education');

        // Fetch mock boards belonging to the program AT sa mismong teacher lang
        // (per-teacher ownership: hindi makikita ng ibang teacher ang gawa ng kapwa niya teacher)
        $mockBoards = MockBoard::where('program', $selectedProgram)
            ->where('teacher_id', $teacher->id)
            ->with(['phases.module.quizQuestions'])
            ->withCount('attempts')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get total students enrolled under this program
        $totalProgramStudents = User::where('role', 'student')
            ->where('program', $selectedProgram)
            ->count();

        // Calculate aggregated overview stats
        $classAverageScore = $mockBoards->avg('average_score') ?? 0;
        $completionRate = $mockBoards->avg('completion_rate') ?? 0;
        $highestScore = $mockBoards->max('highest_score') ?? 0;

        return view('pages.teacher.mock-boards.batch-dashboard', [
            'selectedProgram' => $selectedProgram,
            'mockBoards' => $mockBoards ?? collect(),
            'totalProgramStudents' => $totalProgramStudents,
            'classAverageScore' => $classAverageScore,
            'completionRate' => $completionRate,
            'highestScore' => $highestScore,
        ]);
    }

    /**
     * Update a mock board and sync changes to associated quiz modules.
     */
    public function update(Request $request, MockBoard $mockBoard)
    {
        // 1. Authorization check using your existing Gate
        if (! auth()->user()->can('manage-mock-board', $mockBoard)) {
            abort(403, 'You do not have permission to update this Mock Board.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'program' => 'sometimes|in:education,accountancy,psychology',
            'review_period_start' => 'sometimes|date',
            'review_period_end' => 'sometimes|date|after_or_equal:review_period_start',
            'passing_percentage' => 'sometimes|integer|min:0|max:100',
        ]);

        // 2. Perform the update on the Mock Board itself
        $mockBoard->update($validated);

        // 3. Sync Passing Percentage to the underlying Modules
        if (isset($validated['passing_percentage'])) {
            /** @var MockBoardPhase $phase */
            foreach ($mockBoard->phases as $phase) {
                if ($phase->module) {
                    $phase->module->update([
                        'passing_percentage' => $validated['passing_percentage'],
                    ]);
                }
            }
        }

        // 4. Handle AJAX/JSON requests
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Mock Board updated successfully',
                'mock_board' => $mockBoard->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Mock Board updated successfully.');
    }

    /**
     * Delete a mock board and all its associated phases and modules.
     */
    public function destroy(Request $request, MockBoard $mockBoard)
    {
        // 1. Authorization check
        if (! auth()->user()->can('manage-mock-board', $mockBoard)) {
            abort(403, 'You do not have permission to delete this Mock Board.');
        }

        // 2. Loop through phases to delete the underlying Modules first
        /** @var MockBoardPhase $phase */
        foreach ($mockBoard->phases as $phase) {
            if ($phase->module) {
                $phase->module->delete();
            }
            $phase->delete();
        }

        // 3. Delete the Mock Board record
        $mockBoard->delete();

        // 4. Handle AJAX/JSON requests
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Mock Board and all associated data deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Mock Board deleted successfully.');
    }

    /**
     * Update phase details.
     */
    public function updatePhases(Request $request, MockBoard $mockBoard)
    {
        if (! auth()->user()->can('manage-mock-board', $mockBoard)) {
            abort(403);
        }

        $validated = $request->validate([
            'phases' => 'required|array',
            'phases.pre_test.title' => 'sometimes|string|max:255',
            'phases.pre_test.question_ids' => 'nullable|array',
            'phases.pre_test.is_same_questions' => 'sometimes|boolean',
            'phases.pre_boards.title' => 'sometimes|string|max:255',
            'phases.pre_boards.question_ids' => 'nullable|array',
            'phases.pre_boards.is_same_questions' => 'sometimes|boolean',
        ]);

        foreach ($validated['phases'] as $phaseType => $data) {
            /** @var MockBoardPhase|null $phase */
            $phase = $mockBoard->phases()->where('phase_type', $phaseType)->first();
            if ($phase) {
                $updateData = [];
                if (isset($data['title'])) {
                    $updateData['title'] = $data['title'];
                    if ($phase->module) {
                        $phase->module->update(['title' => $data['title']]);
                    }
                }
                if (isset($data['question_ids'])) {
                    $updateData['question_ids'] = $data['question_ids'];
                }
                if (isset($data['is_same_questions'])) {
                    $updateData['is_same_questions'] = $data['is_same_questions'];
                }
                $phase->update($updateData);
            }
        }

        return response()->json([
            'message' => 'Phases updated successfully',
            'phases' => $mockBoard->fresh()->phases,
        ]);
    }

    /**
     * Add a missing phase (Pre-Test or Pre-Boards) to an existing Mock Board.
     */
    public function addPhase(Request $request, MockBoard $mockBoard)
    {
        if (! auth()->user()->can('manage-mock-board', $mockBoard)) {
            abort(403, 'You do not have permission to modify this Mock Board.');
        }

        $validated = $request->validate([
            'phase_type' => 'required|in:pre_test,pre_boards',
            'title' => 'nullable|string|max:255',
        ]);

        $phaseType = $validated['phase_type'];

        // Iwasan ang duplicate phase
        $existing = $mockBoard->phases()->where('phase_type', $phaseType)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'This phase already exists for this Mock Board.');
        }

        $phaseLabel = $phaseType === 'pre_test' ? 'Pre-Test' : 'Pre-Boards';
        $phaseTitle = $validated['title'] ?? ($mockBoard->title.' - '.$phaseLabel);

        $phaseModule = Module::create([
            'title' => $phaseTitle,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_mock_board' => true,
            'class_id' => $mockBoard->class_id,
            'passing_percentage' => $mockBoard->passing_percentage,
            'time_limit' => 0,
            'created_by' => auth()->id(),
        ]);

        MockBoardPhase::create([
            'mock_board_id' => $mockBoard->id,
            'phase_type' => $phaseType,
            'title' => $phaseTitle,
            'module_id' => $phaseModule->id,
        ]);

        return redirect()
            ->route('quiz.create', $phaseModule)
            ->with('success', "{$phaseLabel} phase created successfully. Start building the exam now.");
    }

    /**
     * Generate AI questions for a phase.
     */
    public function generateQuestions(Request $request, MockBoard $mockBoard, string $phase)
    {
        if (! auth()->user()->can('manage-mock-board', $mockBoard)) {
            abort(403);
        }

        if (! in_array($phase, ['pre_test', 'pre_boards'])) {
            abort(400, 'Invalid phase type');
        }

        $validated = $request->validate([
            'pdf_url' => 'required|string',
            'question_count' => 'required|integer|min:1|max:100',
            'domain' => 'required|string',
        ]);

        $phaseModel = $mockBoard->phases()->where('phase_type', $phase)->firstOrFail();

        $generatedQuestions = [
            [
                'id' => 1,
                'question' => 'Sample generated question 1',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
                'domain' => $validated['domain'],
                'status' => 'pending_approval',
            ],
        ];

        return response()->json([
            'message' => 'Questions generated',
            'questions' => $generatedQuestions,
            'phase' => $phase,
            'note' => 'AI integration pending - this is a placeholder',
        ]);
    }

    /**
     * Approve generated questions and add to module.
     */
    public function approveGeneratedQuestions(Request $request, MockBoard $mockBoard, string $phase)
    {
        if (! auth()->user()->can('manage-mock-board', $mockBoard)) {
            abort(403);
        }

        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array',
            'questions.*.correct_answer' => 'required|string',
        ]);

        /** @var MockBoardPhase $phaseModel */
        $phaseModel = $mockBoard->phases()->where('phase_type', $phase)->firstOrFail();
        $module = $phaseModel->module;

        if (! $module) {
            abort(400, 'Module not found for this phase');
        }

        $createdQuestionIds = [];
        foreach ($validated['questions'] as $qData) {
            $question = Question::create([
                'module_id' => $module->id,
                'question' => $qData['question'],
                'type' => 'multiple_choice',
                'options' => $qData['options'],
                'correct_answer' => $qData['correct_answer'],
                'created_by' => auth()->id(),
            ]);
            $createdQuestionIds[] = $question->id;
        }

        $phaseModel->update([
            'question_ids' => $createdQuestionIds,
        ]);

        return response()->json([
            'message' => count($createdQuestionIds).' questions added to '.$phase,
            'question_ids' => $createdQuestionIds,
        ]);
    }

    /**
     * Get class-level analysis with ANOVA.
     */
    /**
     * Get class-level analysis with ANOVA.
     */
    /**
     * Get class-level analysis with ANOVA.
     */
    /**
     * Redirect to the unified Mock Board Analytics page, pre-filtered
     * to this mock board's program. Dating hiwalay na computation dito,
     * pinagsama na natin sa MockBoardAnalyticsController para iisa
     * na lang ang totoong pinagkukunan ng datos.
     */
    public function classAnalysis(MockBoard $mockBoard)
    {
        if (! auth()->user()->can('view-mock-board', $mockBoard)) {
            abort(403);
        }

        return redirect()->route('admin.mock-board-analytics', [
            'view_type' => 'program',
            'program' => $mockBoard->program,
        ]);
    }

    /**
     * Get individual student analysis.
     */
    public function studentAnalysis(MockBoard $mockBoard, User $student)
    {
        if (! auth()->user()->can('view-mock-board', $mockBoard)) {
            abort(403);
        }

        $attempts = $mockBoard->attempts()
            ->where('user_id', $student->id)
            ->with(['quizAttempt.answers.question'])
            ->get();

        $preTest = $attempts->firstWhere('phase_type', 'pre_test');
        $preBoards = $attempts->firstWhere('phase_type', 'pre_boards');

        return response()->json([
            'student' => $student,
            'mock_board' => $mockBoard,
            'pre_test' => $preTest,
            'pre_boards' => $preBoards,
            'improvement' => ($preTest && $preBoards)
                ? $preBoards->percentage - $preTest->percentage
                : null,
        ]);
    }

    /**
     * Compute ANOVA on demand (AJAX endpoint).
     */
    public function computeANOVA(MockBoard $mockBoard)
    {
        if (! auth()->user()->can('view-mock-board', $mockBoard)) {
            abort(403);
        }

        try {
            $statistics = $this->statisticsService->computeClassStatistics($mockBoard);

            return response()->json([
                'success' => true,
                'statistics' => [
                    'pre_test_mean' => $statistics->pre_test_mean,
                    'pre_test_std_dev' => $statistics->pre_test_std_dev,
                    'pre_test_count' => $statistics->pre_test_count,
                    'pre_boards_mean' => $statistics->pre_boards_mean,
                    'pre_boards_std_dev' => $statistics->pre_boards_std_dev,
                    'pre_boards_count' => $statistics->pre_boards_count,
                    'anova_f_statistic' => $statistics->anova_f_statistic,
                    'anova_p_value' => $statistics->anova_p_value,
                    'anova_significant' => $statistics->anova_significant,
                    'improvement_percentage' => $statistics->improvement_percentage,
                    'interpretation' => $statistics->anova_interpretation,
                    'computed_at' => $statistics->computed_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to compute ANOVA: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new mock board.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'program' => 'required|string', // Ginawa nating string para tanggapin kahit anong format ng program (PSYCH, psychology, etc.)
            'review_period_start' => 'required|date',
            'review_period_end' => 'required|date|after_or_equal:review_period_start',
            'passing_percentage' => 'required|integer|min:0|max:100',
            'selected_phase' => 'required|in:pre_test,pre_boards',
            'pre_test_title' => 'nullable|string|max:255',
            'pre_boards_title' => 'nullable|string|max:255',
            'time_limit' => 'nullable|integer|min:0',
        ]);

        $classId = $validated['class_id'] ?? null;

        // Kung may klase, kunin din ang program mula sa klase para sigurado
        $program = $validated['program'];
        if ($classId) {
            $classModel = ClassModel::find($classId);
            if ($classModel && ! empty($classModel->program)) {
                $program = $classModel->program;
            }
        }

        return DB::transaction(function () use ($validated, $classId, $program) {

            $mockBoard = MockBoard::create([
                'class_id' => $classId,
                'teacher_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'program' => strtolower(trim($program)), // Naka-lowercase para laging consistent sa matching
                'review_period_start' => $validated['review_period_start'],
                'review_period_end' => $validated['review_period_end'],
                'passing_percentage' => $validated['passing_percentage'],
                'visibility' => 'all',
            ]);

            $selectedPhase = $validated['selected_phase'];
            $phaseTitle = $selectedPhase === 'pre_test'
                ? ($validated['pre_test_title'] ?? $validated['title'].' - Pre-Test')
                : ($validated['pre_boards_title'] ?? $validated['title'].' - Pre-Boards');

            $phaseModule = Module::create([
                'title' => $phaseTitle,
                'is_quiz' => true,
                'is_formal_assessment' => true,
                'is_mock_board' => true,
                'class_id' => $classId,
                'passing_percentage' => $validated['passing_percentage'],
                'time_limit' => $validated['time_limit'] ?? 0,
                'created_by' => auth()->id(),
            ]);

            MockBoardPhase::create([
                'mock_board_id' => $mockBoard->id,
                'phase_type' => $selectedPhase,
                'title' => $phaseTitle,
                'module_id' => $phaseModule->id,
            ]);

            $examName = $selectedPhase === 'pre_test' ? 'Pre-Test exam' : 'Pre-Boards exam';

            return redirect()
                ->route('quiz.create', $phaseModule)
                ->with('success', "Mock Board created successfully. Start by building the {$examName} now.");
        });
    }
}
