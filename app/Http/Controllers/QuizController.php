<?php

namespace App\Http\Controllers;

use App\Models\AssessmentAttemptGrant;
use App\Models\ClassModel;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\AiSettingsResolver;
use App\Services\CloudflareAI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    private function buildFallbackQuizInsights(?QuizAttempt $attempt, $answers): array
    {
        if (! $attempt) {
            return [
                'strong' => 'Your quiz result was shown, but the saved attempt details are not available yet.',
                'weak' => 'Detailed answer analysis could not be loaded for this attempt.',
                'recommendation' => 'Refresh once to retry loading insights.',
            ];
        }

        $answers = collect($answers ?? []);
        $correctAnswers = $answers->where('is_correct', true)->values();
        $wrongAnswers = $answers->where('is_correct', false)->values();

        $strong = 'You answered '.$attempt->score.' out of '.$attempt->total.' questions correctly.';
        if ($correctAnswers->isNotEmpty()) {
            $correctTopics = $correctAnswers->take(2)
                ->map(fn ($answer) => trim((string) data_get($answer, 'question.question_text', '')))
                ->filter()->map(fn ($text) => Str::limit($text, 80))->implode(' | ');
            if ($correctTopics !== '') {
                $strong .= ' Strong items: '.$correctTopics.'.';
            }
        }

        $weak = 'No major weak areas detected.';
        if ($wrongAnswers->isNotEmpty()) {
            $weakTopics = $wrongAnswers->take(2)
                ->map(fn ($answer) => trim((string) data_get($answer, 'question.question_text', '')))
                ->filter()->map(fn ($text) => Str::limit($text, 80))->implode(' | ');
            $weak = $weakTopics !== '' ? 'Review: '.$weakTopics.'.' : 'Review missed items.';
        }

        $recommendation = match (true) {
            $attempt->percentage >= 85 => 'Keep the pace and review the missed items once for retention.',
            $attempt->percentage >= 50 => 'Review the incorrect questions and revisit the related lesson sections.',
            default => 'Revisit the module content first, then retake the quiz.',
        };

        return ['strong' => $strong, 'weak' => $weak, 'recommendation' => $recommendation];
    }

    public function getQuestions(Module $module)
    {
        if (! $module->is_quiz) {
            return response()->json(['success' => false, 'message' => 'Not a quiz.'], 400);
        }

        $questions = QuizQuestion::where('module_id', $module->id)->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'questions' => $questions->map(fn ($q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'options' => $q->options,
                'correct' => $q->correct_option,
            ]),
            'time_limit' => $module->time_limit ?? 0,
        ]);
    }

    public function generateInsights(Request $request, Module $module)
    {
        $user = Auth::user();
        $attempt = QuizAttempt::where('user_id', $user->id)->where('module_id', $module->id)->latest('updated_at')->first();

        if (! $attempt || $attempt->ai_strong !== null) {
            return response()->json(['success' => true, 'strong' => $attempt?->ai_strong, 'weak' => $attempt?->ai_weak, 'recommendation' => $attempt?->ai_recommendation]);
        }

        $answers = QuizAnswer::where('attempt_id', $attempt->id)->with('question')->get();
        $fallback = $this->buildFallbackQuizInsights($attempt, $answers);

        try {
            $resolver = app(AiSettingsResolver::class);
            $ai = app(CloudflareAI::class);
            $answersContext = '';
            foreach ($answers as $a) {
                $status = $a->is_correct ? 'Correct' : 'Wrong';
                $answersContext .= "- Q: {$a->question->question_text}\n Answer: {$a->selected_option} -> {$status}\n";
            }

            $userPrompt = $resolver->renderTemplate($resolver->getPromptTemplate('quiz_insights', 'user_template'), [
                'score' => (string) $attempt->percentage,
                'module_title' => (string) $module->title,
                'answers_context' => trim($answersContext),
            ]);

            $result = $ai->run($resolver->getModel(), [
                'messages' => [
                    ['role' => 'system', 'content' => $resolver->getPromptTemplate('quiz_insights', 'system')],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => $resolver->getMaxTokens(),
                'temperature' => 0.6,
            ]);

            $text = $result['response'] ?? '';
            if ($text) {
                $attempt->update(['ai_strong' => $fallback['strong'], 'ai_weak' => $fallback['weak'], 'ai_recommendation' => $fallback['recommendation']]);
            }
        } catch (\Exception $e) {
            $attempt->update(['ai_strong' => $fallback['strong'], 'ai_weak' => $fallback['weak'], 'ai_recommendation' => $fallback['recommendation']]);
        }

        return response()->json(['success' => true, 'strong' => $attempt->ai_strong, 'weak' => $attempt->ai_weak, 'recommendation' => $attempt->ai_recommendation]);
    }

    /**
     * Kunin ang totoong pinapayagang bilang ng attempts para sa estudyanteng
     * ito sa module na ito = base max_attempts ng module + kung may extra
     * grant na ibinigay ng teacher.
     */
    private function getAllowedAttempts(Module $module, int $userId): int
    {
        return $module->allowedAttemptsFor($userId);
    }

    /**
     * I-mark ang simula ng pagkuha ng quiz — dito gagawa ng record kahit
     * hindi pa nag-a-answer ang estudyante, para hindi mawala ang bakas
     * kapag nag-back/nag-abandon sila bago matapos.
     */
    public function startAttempt(Module $module)
    {
        $user = Auth::user();

        $attempt = QuizAttempt::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();

        if (! $attempt) {
            // Unang pagkakataon kukuha — palaging pinapayagan, dahil 1 pa lang ang gagamitin.
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'attempt_count' => 1,
                'score' => 0,
                'total' => 0,
                'percentage' => 0,
                'passed' => false,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        } elseif ($attempt->status === 'in_progress') {
            // Nag-abandon dati (halimbawa back button), pinatuloy — huwag na dagdagan ang count
            $attempt->update(['started_at' => now()]);
        } else {
            // Natapos na dati — ito ay bagong pagsubok. I-enforce ang attempt limit
            // dito lamang para sa formal assessments (Pre-Test, Post-Test, Mock Board).
            // Ang mga practice modules (is_formal_assessment = false) ay walang limitasyon.
            if ($module->is_formal_assessment) {
                $allowed = $this->getAllowedAttempts($module, $user->id);
                $used = $attempt->attempt_count ?? 1;

                if ($used >= $allowed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Naubos na ang mga pinapayagang pagsubok mo para sa assessment na ito. Makipag-ugnayan sa iyong guro kung kailangan mo ng karagdagang pagkakataon.',
                        'attempts_used' => $used,
                        'attempts_allowed' => $allowed,
                    ], 403);
                }
            }

            $attempt->update([
                'attempt_count' => ($attempt->attempt_count ?? 0) + 1,
                'score' => 0,
                'total' => 0,
                'percentage' => 0,
                'passed' => false,
                'status' => 'in_progress',
                'started_at' => now(),
                'completed_at' => null,
                'ai_strong' => null,
                'ai_weak' => null,
                'ai_recommendation' => null,
            ]);

            // Burahin ang lumang sagot mula sa naunang pagsubok
            QuizAnswer::where('attempt_id', $attempt->id)->delete();
        }

        return response()->json([
            'success' => true,
            'attempt_count' => $attempt->attempt_count,
        ]);
    }

    public function submitAnswer(Request $request, Module $module)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'question_id' => 'required|exists:quiz_questions,id',
            'selected_option' => 'required',
        ]);

        $question = QuizQuestion::findOrFail($validated['question_id']);

        // Dapat meron nang attempt row mula sa startAttempt() — pero panatilihin
        // ang fallback na ito bilang safety net kung sakaling hindi na-tawag ang start.
        $attempt = QuizAttempt::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            [
                'attempt_count' => 1,
                'score' => 0,
                'total' => 0,
                'percentage' => 0,
                'passed' => false,
                'status' => 'in_progress',
                'started_at' => now(),
            ]
        );

        $selected = trim($validated['selected_option']);
        $correct = trim($question->correct_option);
        $isCorrect = (strcasecmp($selected, $correct) === 0);

        QuizAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
            ['selected_option' => $selected, 'is_correct' => $isCorrect]
        );

        return response()->json(['success' => true, 'isCorrect' => $isCorrect]);
    }

    public function submitQuiz(Request $request, Module $module)
    {
        $user = Auth::user();

        return DB::transaction(function () use ($user, $module, $request) {
            $attempt = QuizAttempt::firstOrCreate(
                ['user_id' => $user->id, 'module_id' => $module->id],
                [
                    'attempt_count' => 1,
                    'score' => 0,
                    'total' => 0,
                    'percentage' => 0,
                    'passed' => false,
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]
            );

            $answers = QuizAnswer::where('attempt_id', $attempt->id)->get();
            $correctCount = $answers->where('is_correct', true)->count();
            $totalQuestions = $answers->count() ?: (int) $request->input('total', 0);

            if ($totalQuestions === 0) {
                $totalQuestions = QuizQuestion::where('module_id', $module->id)->count();
            }

            $percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

            $attempt->update([
                'score' => $correctCount,
                'total' => $totalQuestions,
                'percentage' => $percentage,
                'passed' => $percentage >= ($module->passing_grade ?? 50),
                'status' => 'completed',
                'completed_at' => now(),
                'ai_strong' => null,
            ]);

            // SYNC TO MOCK BOARD ATTEMPTS (Para lumabas sa Teacher/Admin Analytics)
            $mockPhase = MockBoardPhase::where('module_id', $module->id)->first();
            if ($mockPhase) {
                $passingGrade = 75;
                if ($mockPhase->mockBoard) {
                    $passingGrade = $mockPhase->mockBoard->passing_percentage ?? 75;
                }

                MockBoardAttempt::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'mock_board_id' => $mockPhase->mock_board_id,
                        'phase_type' => $mockPhase->phase_type,
                    ],
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'score' => $correctCount,
                        'total' => $totalQuestions,
                        'percentage' => $percentage,
                        'passed' => $percentage >= $passingGrade,
                    ]
                );
            }

            return response()->json(['success' => true, 'score' => $correctCount, 'percentage' => $percentage]);
        });
    }

    /**
     * Teacher: itakda ang base na bilang ng attempts na pinapayagan sa
     * lahat ng estudyante para sa module/assessment na ito.
     */
    public function updateMaxAttempts(Request $request, Module $module)
    {
        $class = $module->class;
        $isOwnerOrAdmin = $class
            ? ($class->created_by === Auth::id() || Auth::user()->role === 'admin')
            : (Auth::user()->role === 'admin' || Auth::user()->role === 'teacher');

        if (! $isOwnerOrAdmin) {
            abort(403);
        }

        $validated = $request->validate([
            'max_attempts' => 'required|integer|min:1|max:20',
        ]);

        $module->update(['max_attempts' => $validated['max_attempts']]);

        return response()->json([
            'success' => true,
            'message' => 'Naka-set na ngayon sa '.$validated['max_attempts'].' ang base na attempts para sa assessment na ito.',
            'max_attempts' => $module->max_attempts,
        ]);
    }

    /**
     * Teacher: magbigay ng karagdagang attempts sa isang partikular na
     * estudyante, bukod sa base max_attempts ng module.
     */
    public function grantExtraAttempt(Request $request, Module $module, User $student)
    {
        $class = $module->class;
        $isOwnerOrAdmin = $class
            ? ($class->created_by === Auth::id() || Auth::user()->role === 'admin')
            : (Auth::user()->role === 'admin' || Auth::user()->role === 'teacher');

        if (! $isOwnerOrAdmin) {
            abort(403);
        }

        $validated = $request->validate([
            'extra_attempts' => 'required|integer|min:1|max:10',
            'reason' => 'nullable|string|max:255',
        ]);

        $grant = AssessmentAttemptGrant::updateOrCreate(
            ['module_id' => $module->id, 'user_id' => $student->id],
            [
                'extra_attempts' => $validated['extra_attempts'],
                'granted_by' => Auth::id(),
                'reason' => $validated['reason'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $student->name.' ay bibigyan ng '.$validated['extra_attempts'].' karagdagang attempt(s).',
            'total_allowed' => ($module->max_attempts ?? 1) + $grant->extra_attempts,
        ]);
    }

    public function resetMyAttempt(Module $module)
    {
        // Ang self-service reset ay libre lang para sa practice modules.
        // Sa formal assessments (Pre-Test, Post-Test, Mock Board), kailangang
        // dumaan sa teacher-granted extra attempt (startAttempt() ang nag-e-enforce nito).
        if ($module->is_formal_assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Hindi maaaring i-reset ang sariling attempt sa formal assessment na ito. Makipag-ugnayan sa iyong guro para humingi ng karagdagang pagkakataon.',
            ], 403);
        }

        $user = Auth::user();
        $attempt = QuizAttempt::where('user_id', $user->id)->where('module_id', $module->id)->first();
        if ($attempt) {
            QuizAnswer::where('attempt_id', $attempt->id)->delete();
            $attempt->update(['score' => 0, 'percentage' => 0, 'ai_strong' => null]);
        }

        return response()->json(['success' => true]);
    }

    public function show(Module $module)
    {
        return view('quiz.show', [
            'module' => $module,
            'questions' => $module->quizQuestions,
            'durationSeconds' => ($module->time_limit ?? 0) * 60,
        ]);
    }

    public function createQuizDraft(Request $request, ClassModel $class)
    {
        $rawTime = $request->input('time_limit', null);
        $minutes = is_numeric($rawTime) ? (int) $rawTime : 0;

        $module = Module::create([
            'class_id' => $class->id,
            'title' => $request->title,
            'description' => $request->description,
            'time_limit' => $minutes,
            'is_quiz' => true,
            'is_formal_assessment' => (bool) $request->is_formal_assessment,
            'assessment_purpose' => $request->input('assessment_purpose'),
            'visibility' => $request->input('visibility', 'all'),
        ]);

        if ($request->visibility !== 'all' && $request->has('visible_user_ids')) {
            $module->visibleTo()->sync($request->visible_user_ids);
        }

        return redirect()->route('quiz.create', $module);
    }
}
