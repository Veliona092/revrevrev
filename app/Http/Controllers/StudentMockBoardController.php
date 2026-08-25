<?php

namespace App\Http\Controllers;

use App\Models\MockBoard;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptSnapshot;
use App\Services\MockBoardStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StudentMockBoardController extends Controller
{
    /**
     * Maps short program codes to their full names.
     * Used to normalize mismatched program values (e.g. "psych" vs "psychology").
     */
    private array $programMap = [
        'psych' => 'psychology',
        'educ' => 'education',
        'accountancy' => 'accountancy',
    ];

    public function __construct(
        private MockBoardStatisticsService $statisticsService
    ) {}

    /**
     * Normalize a program string: lowercase, trimmed, mapped to full name.
     */
    private function normalizeProgram(?string $program): string
    {
        $raw = strtolower(trim($program ?? ''));
        return $this->programMap[$raw] ?? $raw;
    }

    /**
     * Check if a mock board's program matches the user's program,
     * regardless of case or short-code vs full-name differences.
     */
    private function programsMatch(MockBoard $mockBoard, $user): bool
    {
        return $this->normalizeProgram($mockBoard->program) === $this->normalizeProgram($user->program);
    }

    /**
     * Teacher: list mock boards na sarili niyang ginawa, naka-scope sa program niya.
     */
   public function index(Request $request)
{
    $user = auth()->user();

    if (in_array($user->role, ['teacher', 'admin'], true)) {
        $normalizedProgram = strtolower(trim($user->program ?? ''));

        $mockBoards = MockBoard::where('teacher_id', $user->id)
            ->when($normalizedProgram !== '', function ($q) use ($normalizedProgram) {
                $q->whereRaw('LOWER(program) = ?', [$normalizedProgram]);
            })
            ->with(['phases.module.quizQuestions'])
            ->withCount('attempts')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.teacher.mock-boards.batch-dashboard', [
            'mockBoards'      => $mockBoards,
            'selectedProgram' => $normalizedProgram,
        ]);
    }

    // Student: makita ang mga APPROVED mock boards na naka-match sa program niya
    $normalizedStudentProgram = $this->normalizeProgram($user->program);

    $availableBoards = MockBoard::where('status', 'approved')
        ->with([
            'phases',
            'attempts' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            },
        ])
        ->get()
        ->filter(fn ($mb) => $this->normalizeProgram($mb->program) === $normalizedStudentProgram)
        ->values();

    $programLayoutMap = [
        'psych' => 'layouts.appPsych',
        'accountancy' => 'layouts.appAcc',
        'educ' => 'layouts.app',
    ];
    $layout = $programLayoutMap[$user->program] ?? 'layouts.app';

    return view('pages.student.mock-boards.index', [
        'availableBoards' => $availableBoards,
        'layout' => $layout,
    ]);

    

}    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'review_period_start' => 'required|date',
            'review_period_end' => 'required|date|after_or_equal:review_period_start',
            'passing_percentage' => 'required|integer|min:0|max:100',
            'selected_phase' => 'required|in:pre_test,pre_boards',
            'pre_test_title' => 'nullable|string|max:255',
            'pre_boards_title' => 'nullable|string|max:255',
            'time_limit' => 'nullable|integer|min:0',
        ]);

        // Ang program ay palaging kinukuha mula sa account ng teacher, hindi user input —
        // iniiwasan ang pag-upload ng mock board sa maling program.
        $program = strtolower(trim($user->program ?? ''));

        if ($program === '') {
            return redirect()->back()->with('error', 'Your account has no assigned program. Contact an admin.');
        }

        return DB::transaction(function () use ($validated, $program, $user) {

            $mockBoard = MockBoard::create([
                'teacher_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'program' => $program,
                'review_period_start' => $validated['review_period_start'],
                'review_period_end' => $validated['review_period_end'],
                'passing_percentage' => $validated['passing_percentage'],
                'status' => 'pending',
            ]);

            $selectedPhase = $validated['selected_phase'];
            $phaseTitle = $selectedPhase === 'pre_test'
                ? ($validated['pre_test_title'] ?? $validated['title'] . ' - Pre-Test')
                : ($validated['pre_boards_title'] ?? $validated['title'] . ' - Pre-Boards');

            $phaseModule = Module::create([
                'title' => $phaseTitle,
                'is_quiz' => true,
                'is_formal_assessment' => true,
                'is_mock_board' => true,
                'class_id' => null,
                'passing_percentage' => $validated['passing_percentage'],
                'time_limit' => $validated['time_limit'] ?? 0,
                'created_by' => $user->id,
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
                ->with('success', "Mock Board created and submitted for admin approval. Start by building the {$examName} now.");
        });
    }

    /**
     * Teacher: i-update ang sariling mock board — babalik sa pending kung may nabagong laman.
     */
    public function update(Request $request, MockBoard $mockBoard)
    {
        $user = auth()->user();

        if ($mockBoard->teacher_id !== $user->id) {
            abort(403, 'You do not have permission to update this Mock Board.');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'review_period_start' => 'sometimes|date',
            'review_period_end' => 'sometimes|date|after_or_equal:review_period_start',
            'passing_percentage' => 'sometimes|integer|min:0|max:100',
        ]);

        $mockBoard->update($validated);

        // Anumang pagbabago sa laman ay kailangang muling i-review ng admin
        $mockBoard->resetToPending();

        if (isset($validated['passing_percentage'])) {
            foreach ($mockBoard->phases as $phase) {
                if ($phase->module) {
                    $phase->module->update([
                        'passing_percentage' => $validated['passing_percentage'],
                    ]);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Mock Board updated and resubmitted for approval.',
                'mock_board' => $mockBoard->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Mock Board updated and resubmitted for admin approval.');
    }

    /**
     * Teacher: burahin ang sariling mock board lamang.
     */
    public function destroy(Request $request, MockBoard $mockBoard)
    {
        $user = auth()->user();

        if ($mockBoard->teacher_id !== $user->id) {
            abort(403, 'You do not have permission to delete this Mock Board.');
        }

        foreach ($mockBoard->phases as $phase) {
            if ($phase->module) {
                $phase->module->delete();
            }
            $phase->delete();
        }

        $mockBoard->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Mock Board and all associated data deleted successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Mock Board deleted successfully.');
    }

    /**
     * Teacher: magdagdag ng missing phase (Pre-Test o Pre-Boards) sa sariling mock board.
     */
    public function addPhase(Request $request, MockBoard $mockBoard)
    {
        $user = auth()->user();

        if ($mockBoard->teacher_id !== $user->id) {
            abort(403, 'You do not have permission to modify this Mock Board.');
        }

        $validated = $request->validate([
            'phase_type' => 'required|in:pre_test,pre_boards',
            'title' => 'nullable|string|max:255',
        ]);

        $phaseType = $validated['phase_type'];

        $existing = $mockBoard->phases()->where('phase_type', $phaseType)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'This phase already exists for this Mock Board.');
        }

        $phaseLabel = $phaseType === 'pre_test' ? 'Pre-Test' : 'Pre-Boards';
        $phaseTitle = $validated['title'] ?? ($mockBoard->title . ' - ' . $phaseLabel);

        $phaseModule = Module::create([
            'title' => $phaseTitle,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'is_mock_board' => true,
            'class_id' => null,
            'passing_percentage' => $mockBoard->passing_percentage,
            'time_limit' => 0,
            'created_by' => $user->id,
        ]);

        MockBoardPhase::create([
            'mock_board_id' => $mockBoard->id,
            'phase_type' => $phaseType,
            'title' => $phaseTitle,
            'module_id' => $phaseModule->id,
        ]);

        // Content changed — needs re-approval
        $mockBoard->resetToPending();

        return redirect()
            ->route('quiz.create', $phaseModule)
            ->with('success', "{$phaseLabel} phase created successfully. Start building the exam now.");
    }

    /**
     * Teacher: i-update ang phase details (title, question_ids, is_same_questions).
     */
    public function updatePhases(Request $request, MockBoard $mockBoard)
    {
        $user = auth()->user();

        if ($mockBoard->teacher_id !== $user->id) {
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

        // Content changed — needs re-approval
        $mockBoard->resetToPending();

        return response()->json([
            'message' => 'Phases updated successfully and resubmitted for approval',
            'phases' => $mockBoard->fresh()->phases,
        ]);
    }

    /**
     * Show mock board details via JSON (for modals or dynamic views).
     */
    public function show(MockBoard $mockBoard)
    {
        $user = auth()->user();

        if (!$this->programsMatch($mockBoard, $user)) {
            abort(403, 'This Mock Board is not assigned to your program.');
        }

        if (!$mockBoard->isApproved()) {
            abort(403, 'This Mock Board is not yet available.');
        }

        $mockBoard->load(['phases.module.quizQuestions', 'statistics']);

        $attempts = $mockBoard->attempts()
            ->where('user_id', $user->id)
            ->with('quizAttempt')
            ->get();

        $preTest = $attempts->firstWhere('phase_type', 'pre_test');
        $preBoards = $attempts->firstWhere('phase_type', 'pre_boards');

        return response()->json([
            'mock_board' => $mockBoard,
            'pre_test' => $preTest,
            'pre_boards' => $preBoards,
            'can_take_pre_test' => $this->canTakePhase($mockBoard, 'pre_test', $user),
            'can_take_pre_boards' => $this->canTakePhase($mockBoard, 'pre_boards', $user),
            'improvement' => ($preTest && $preBoards) ? $preBoards->percentage - $preTest->percentage : null,
        ]);
    }

    /**
     * Take a specific mock board phase exam.
     */
   public function take(MockBoard $mockBoard, string $phase)
{
    $user = auth()->user();

    if (!$this->programsMatch($mockBoard, $user)) {
        abort(403, 'This Mock Board is not assigned to your program.');
    }

    if (!$mockBoard->isApproved()) {
        abort(403, 'This Mock Board is not yet available.');
    }

    if (!in_array($phase, ['pre_test', 'pre_boards']) || !$this->canTakePhase($mockBoard, $phase, $user)) {
        abort(403, 'Phase not available.');
    }

    $mockBoardPhase = $mockBoard->phases()
        ->where('phase_type', $phase)
        ->with(['module.quizQuestions'])
        ->firstOrFail();

    $module = $mockBoardPhase->module;
    $questions = $module->quizQuestions;

    $attemptsUsed = QuizAttempt::where('user_id', $user->id)
        ->where('module_id', $module->id)
        ->where('mock_board_id', $mockBoard->id)
        ->whereNotNull('completed_at')
        ->count();

    $attemptsAllowed = $module->max_attempts;
    $canStartAttempt = is_null($attemptsAllowed) || $attemptsUsed < $attemptsAllowed;
    $isResuming = false; // mock board attempts are only recorded on submit, so there's nothing to resume

    if (! $canStartAttempt) {
        return redirect()
            ->route('student.mock-boards.index')
            ->with('error', 'You have no attempts remaining for this exam.');
    }

    return view('pages.student.assessment-take', [
        'module' => $module,
        'questions' => $questions,
        'isMockBoard' => true,
        'mockBoard' => $mockBoard,
        'mockBoardPhase' => $mockBoardPhase,
        'phase' => $phase,
        'can_start_attempt' => $canStartAttempt,
        'is_resuming' => $isResuming,
        'attempts_used' => $attemptsUsed,
        'attempts_allowed' => $attemptsAllowed,
    ]);
}
    /**
     * Submit mock board answers.
     */
    public function submit(Request $request, MockBoard $mockBoard, string $phase)
    {
        $user = auth()->user();

        if (!$this->programsMatch($mockBoard, $user)) {
            return response()->json(['message' => 'Unauthorized for this program'], 403);
        }

        try {
            if (!$request->has('answers')) {
                return response()->json(['message' => 'No answers received'], 422);
            }

            $mockBoardPhase = $mockBoard->phases()
                ->where('phase_type', $phase)
                ->with('module.quizQuestions')
                ->firstOrFail();

            $module = $mockBoardPhase->module;
            $questions = $module->quizQuestions->keyBy('id');

            $score = 0;
            $total = count($request->answers);
            $processedAnswers = [];

            foreach ($request->answers as $ans) {
                $question = $questions->get($ans['question_id'] ?? null);
                $isCorrect = $question ? $this->checkAnswer($question, $ans['answer'] ?? null) : false;

                if ($isCorrect) $score++;

                $processedAnswers[] = [
                    'question_id' => $ans['question_id'],
                    'answer' => $ans['answer'],
                    'is_correct' => $isCorrect,
                ];
            }

            $percentage = $total > 0 ? round(($score / $total) * 100) : 0;

            return DB::transaction(function () use ($user, $module, $mockBoard, $phase, $score, $total, $percentage, $processedAnswers) {

                // 1. Save standard Quiz Attempt
                $quizAttempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'mock_board_id' => $mockBoard->id,
                    'score' => $score,
                    'total' => $total,
                    'percentage' => $percentage,
                    'passed' => $percentage >= ($module->passing_grade ?? 75),
                    'completed_at' => now(),
                ]);

                foreach ($processedAnswers as $answerData) {
                    $quizAttempt->answers()->create($answerData);
                }

                // 2. Save/Update MockBoardAttempt
                $existing = MockBoardAttempt::where([
                    'user_id' => $user->id,
                    'mock_board_id' => $mockBoard->id,
                    'phase_type' => $phase,
                ])->first();

                $mockBoardAttempt = MockBoardAttempt::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'mock_board_id' => $mockBoard->id,
                        'phase_type' => $phase,
                    ],
                    [
                        'quiz_attempt_id' => $quizAttempt->id,
                        'score' => $score,
                        'total' => $total,
                        'percentage' => $percentage,
                        'passed' => $percentage >= $mockBoard->passing_percentage,
                        'attempt_count' => $existing ? ($existing->attempt_count + 1) : 1,
                        // Reset cached AI insights so the frontend re-fetches fresh
                        // ones for this attempt instead of showing stale/wrong text
                        // from a previous submission.
                        'ai_strong' => null,
                        'ai_weak' => null,
                        'ai_recommendation' => null,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'redirect' => route('student.mock-boards.index')
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Mock Board Submit Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get AI Insights for an attempt.
     */
    public function insights(Request $request, MockBoard $mockBoard, string $phase)
    {
        $user = auth()->user();

        $attempt = MockBoardAttempt::where([
            'user_id' => $user->id,
            'mock_board_id' => $mockBoard->id,
            'phase_type' => $phase,
        ])->with('quizAttempt.answers.question')->first();

        if (!$attempt || !$attempt->quizAttempt) {
            return response()->json(['message' => 'Attempt data not found.'], 404);
        }

        $subjectPerformance = [];

        foreach ($attempt->quizAttempt->answers as $answer) {
            $question = $answer->question;
            $subject = $question->category ?? $question->subject ?? 'General Assessment';

            if (!isset($subjectPerformance[$subject])) {
                $subjectPerformance[$subject] = ['correct' => 0, 'total' => 0];
            }

            $subjectPerformance[$subject]['total']++;
            if ($answer->is_correct) {
                $subjectPerformance[$subject]['correct']++;
            }
        }

        $strongAreas = [];
        $weakAreas = [];

        foreach ($subjectPerformance as $subject => $data) {
            $accuracy = ($data['correct'] / $data['total']) * 100;

            if ($accuracy >= 75) {
                $strongAreas[] = "$subject (" . round($accuracy) . "% Mastery)";
            } else {
                $weakAreas[] = "$subject (" . round($accuracy) . "% Mastery)";
            }
        }

        if (empty($subjectPerformance)) {
            $recommendation = "No answers were recorded for this attempt, so we can't generate insights. Make sure to select an answer for each question before submitting.";
        } elseif (empty($weakAreas)) {
            $recommendation = "Excellent performance across all tested categories! Keep up the great work to maintain your edge for the board exams.";
        } else {
            $recommendation = "Action Required: Prioritize reviewing your low-scoring concepts, specifically targeting " . implode(', ', array_map(fn($val) => explode(' (', $val)[0], $weakAreas)) . ".";
        }

        $attempt->update([
            'ai_strong' => empty($strongAreas) ? 'None identified yet' : implode(', ', $strongAreas),
            'ai_weak' => empty($weakAreas) ? 'None identified yet' : implode(', ', $weakAreas),
            'ai_recommendation' => $recommendation,
        ]);

        return response()->json([
            'strong_areas' => $strongAreas,
            'weak_areas' => $weakAreas,
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * Helper to check if a student is allowed to take a phase based on program.
     */
    private function canTakePhase(MockBoard $mockBoard, string $phase, $user): bool
    {
        if (!$this->programsMatch($mockBoard, $user)) {
            return false;
        }

        // Pansamantalang tinanggal ang date restriction para ma-access agad ang mock boards
        return true;
    }

    private function checkAnswer($question, $answer): bool
    {
        $correctAnswer = $question->correct_answer;
        return is_array($correctAnswer) ? in_array($answer, $correctAnswer) : $answer == $correctAnswer;
    }
    /**
     * Ipakita ang buod ng performance (Pre-Test vs Pre-Boards) ng estudyante
     * para sa isang partikular na mock board, kasama na ang AI Insights
     * (cached mula sa MockBoardAttempt) at Attempt History (mula sa
     * QuizAttempt records), kagaya ng result screen sa assessment.take.blade.
     */
    public function results(MockBoard $mockBoard)
    {
        $user = auth()->user();

        if (!$this->programsMatch($mockBoard, $user)) {
            abort(403, 'This Mock Board is not assigned to your program.');
        }

        $mockBoard->load('phases.module');

        $rawAttempts = MockBoardAttempt::where('user_id', $user->id)
            ->where('mock_board_id', $mockBoard->id)
            ->get()
            ->keyBy('phase_type');

        // I-map papunta sa istruktura na inaasahan ng Blade view
        // (total_questions sa halip na total, para tugma sa results.blade.php).
        // Kasama na rin ang cached AI insights (kung meron na).
        $attempts = $rawAttempts->map(function ($attempt) {
            return (object) [
                'score' => $attempt->score,
                'total_questions' => $attempt->total,
                'percentage' => $attempt->percentage,
                'passed' => $attempt->passed,
                'ai_strong' => $attempt->ai_strong,
                'ai_weak' => $attempt->ai_weak,
                'ai_recommendation' => $attempt->ai_recommendation,
            ];
        });

        // Buuin ang Attempt History (per phase) mula sa QuizAttempt records,
        // kasama ang per-question breakdown para sa expandable view.
        $history = [];

        foreach (['pre_test', 'pre_boards'] as $phaseType) {
            $phaseModel = $mockBoard->phases->firstWhere('phase_type', $phaseType);

            if (!$phaseModel || !$phaseModel->module_id) {
                $history[$phaseType] = [];
                continue;
            }

            // QuizAttemptSnapshot is the dedicated append-only history table —
            // one row per completed attempt, unlike QuizAttempt which is a
            // single overwritten row per (user, module, mock_board_id) and
            // can never hold more than one entry's worth of history.
            $history[$phaseType] = QuizAttemptSnapshot::where('user_id', $user->id)
                ->where('module_id', $phaseModel->module_id)
                ->where('mock_board_id', $mockBoard->id)
                ->orderBy('attempt_number', 'asc')
                ->get()
                ->map(function ($snap) {
                    return [
                        'attempt_number' => $snap->attempt_number,
                        'score' => $snap->score,
                        'total' => $snap->total,
                        'percentage' => $snap->percentage,
                        'passed' => $snap->passed,
                        'completed_at' => optional($snap->completed_at)->toIso8601String(),
                        'questions' => collect($snap->questions_snapshot ?? [])->map(function ($q) {
                            return [
                                'question_text' => $q['question_text'] ?? '',
                                'options' => $q['options'] ?? [],
                                'selected_option' => $q['selected_option'] ?? null,
                                'correct_option' => $q['correct_option'] ?? null,
                                'is_correct' => (bool) ($q['is_correct'] ?? false),
                            ];
                        })->values(),
                    ];
                })
                ->values();
        }

        return view('pages.student.mock-boards.results', [
            'mockBoard' => $mockBoard,
            'attempts' => $attempts,
            'history' => $history,
        ]);
    }
}