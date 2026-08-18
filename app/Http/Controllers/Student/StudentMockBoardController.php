<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MockBoard;
use Illuminate\Http\Request;
use App\Models\MockBoardAttempt;
use App\Services\MockBoardStatisticsService;

class StudentMockBoardController extends Controller
{
    // This part is the "bridge" that fixes the error
    public function __construct(
        private MockBoardStatisticsService $statisticsService
    ) {
    }

    public function index()
    {
        $user = auth()->user();
        
        $availableBoards = MockBoard::where('program', $user->program)
            ->with(['attempts' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.student.mock-boards.index', compact('availableBoards'));
    }

    public function take(MockBoard $mockBoard, string $phase)
    {
        $user = auth()->user();

        $existing = $mockBoard->attempts()
            ->where('user_id', $user->id)
            ->where('phase_type', $phase)
            ->exists();

        if ($existing) {
            return redirect()->route('student.mock-boards.index')->with('error', 'You have already completed this phase.');
        }

        $questions = $mockBoard->questions()->inRandomOrder()->get();

        return view('pages.student.mock-boards.exam', [
            'mockBoard' => $mockBoard,
            'phase' => $phase,
            'questions' => $questions,
            'timeLimit' => 60 
        ]);
    }

    public function store(Request $request, MockBoard $mockBoard, string $phase)
    {
        $user = auth()->user();
        $answers = $request->input('answers'); 
        
        $questions = $mockBoard->questions;
        $totalQuestions = $questions->count();
        $correctCount = 0;

        foreach ($questions as $question) {
            $studentAnswer = $answers[$question->id] ?? null;
            if ($studentAnswer === $question->correct_answer) {
                $correctCount++;
            }
        }

        $percentage = ($totalQuestions > 0) ? ($correctCount / $totalQuestions) * 100 : 0;
        $passed = $percentage >= 75;

        $attempt = MockBoardAttempt::create([
            'user_id' => $user->id,
            'mock_board_id' => $mockBoard->id,
            'phase_type' => $phase,
            'score' => $correctCount,
            'total_questions' => $totalQuestions,
            'percentage' => round($percentage, 2),
            'passed' => $passed,
        ]);

        // Now this will work because the service was injected in the constructor
        $this->statisticsService->updateMockBoardSummary($mockBoard);

        return response()->json([
            'success' => true,
            'redirect' => route('student.mock-boards.index'),
            'message' => 'Exam submitted successfully!'
        ]);
    }
    public function results(MockBoard $mockBoard)
{
    $user = auth()->user();

    if (!$this->programsMatch($mockBoard, $user)) {
        abort(403, 'This Mock Board is not assigned to your program.');
    }

    $rawAttempts = $mockBoard->attempts()
        ->where('user_id', $user->id)
        ->get()
        ->keyBy('phase_type');

    $attempts = [];
    foreach (['pre_test', 'pre_boards'] as $phase) {
        if ($rawAttempts->has($phase)) {
            $a = $rawAttempts->get($phase);
            $attempts[$phase] = (object) [
                'percentage' => round($a->percentage),
                'score' => $a->score,
                'total_questions' => $a->total,
                'passed' => $a->passed,
            ];
        }
    }

    return view('pages.student.mock-boards.results', [
        'mockBoard' => $mockBoard,
        'attempts' => $attempts,
    ]);
}
}