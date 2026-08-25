<?php

namespace App\Http\Controllers;

use App\Models\AssessmentAttemptGrant;
use App\Models\ClassModel;
use App\Models\MockBoardAttempt;
use App\Models\MockBoardPhase;
use App\Models\Module;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptSnapshot;
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

        $attemptId = $request->input('attempt_id');
        $attempt = null;

        if (! empty($attemptId)) {
            $attempt = QuizAttempt::where('id', $attemptId)
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->first();
        }

        if (! $attempt) {
            // Fallback lang para sa mga lumang cached frontend na hindi pa
            // nagpapasa ng attempt_id.
            $mockPhase = MockBoardPhase::where('module_id', $module->id)->first();

            $attempt = QuizAttempt::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('mock_board_id', $mockPhase?->mock_board_id)
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->first();
        }

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

            // Always persist the fallback insights regardless of whether the
            // AI call returned usable text — an empty/blank AI response should
            // not leave ai_strong/ai_weak/ai_recommendation stuck at null.
            $attempt->update(['ai_strong' => $fallback['strong'], 'ai_weak' => $fallback['weak'], 'ai_recommendation' => $fallback['recommendation']]);
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

        $mockPhase = MockBoardPhase::where('module_id', $module->id)->first();
        $mockBoardId = $mockPhase?->mock_board_id;

        $attempt = QuizAttempt::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('mock_board_id', $mockBoardId)
            ->first();

        // Kung "in_progress" ang naunang attempt, i-check muna kung lumagpas na
        // sa 1 minuto mula sa huling activity (updated_at, na na-touch() sa
        // bawat submitAnswer()). Kung lumagpas na, ituring itong "timed out":
        // markahan bilang 0/failed, at ituloy sa baba bilang bagong pagsubok.
        $treatAsFreshStart = false;

        if ($attempt && $attempt->status === 'in_progress') {
            $minutesSinceActivity = $attempt->updated_at->diffInMinutes(now());

            if ($minutesSinceActivity > 1) {
                QuizAnswer::where('attempt_id', $attempt->id)->delete();

                $attempt->update([
                    'score' => 0,
                    'total' => $attempt->total ?: $module->quizQuestions()->count(),
                    'percentage' => 0,
                    'passed' => false,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'ai_strong' => null,
                    'ai_weak' => null,
                    'ai_recommendation' => null,
                ]);

                $treatAsFreshStart = true;
            }
        }

        if (! $attempt) {
            // Unang pagkakataon kukuha — palaging pinapayagan, dahil 1 pa lang ang gagamitin.
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'mock_board_id' => $mockBoardId,
                'attempt_count' => 1,
                'score' => 0,
                'total' => 0,
                'percentage' => 0,
                'passed' => false,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        } elseif ($attempt->status === 'in_progress' && ! $treatAsFreshStart) {
            // Nag-abandon dati (halimbawa back button) pero loob pa ng 1 minuto —
            // pinatuloy, huwag na dagdagan ang count
            $attempt->update(['started_at' => now()]);
        } else {
            // Natapos na dati (o kakatapos lang dahil sa timeout sa itaas) —
            // ito ay bagong pagsubok. I-enforce ang attempt limit dito lamang
            // para sa formal assessments (Pre-Test, Post-Test, Mock Board).
            // Ang mga practice modules (is_formal_assessment = false) ay walang limitasyon.
            if ($module->is_formal_assessment) {
                $allowed = $this->getAllowedAttempts($module, $user->id);
                $used = $attempt->attempt_count ?? 1;

                if ($used >= $allowed) {
                    return response()->json([
                        'success' => false,
                        'message' => $treatAsFreshStart
                            ? 'Nag-timeout ang naunang pagsubok mo (lumagpas sa 1 minuto bago bumalik) at nabigyan ng markang 0. Naubos na rin ang iyong mga pinapayagang pagsubok. Makipag-ugnayan sa iyong guro kung kailangan mo ng karagdagang pagkakataon.'
                            : 'Naubos na ang mga pinapayagang pagsubok mo para sa assessment na ito. Makipag-ugnayan sa iyong guro kung kailangan mo ng karagdagang pagkakataon.',
                        'attempts_used' => $used,
                        'attempts_allowed' => $allowed,
                        'timed_out' => $treatAsFreshStart,
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
            'timed_out' => $treatAsFreshStart,
            'attempt_id' => $attempt->id,
        ]);
    }

    public function submitAnswer(Request $request, Module $module)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'question_id' => 'required|exists:quiz_questions,id',
            'selected_option' => 'required',
            'attempt_id' => 'nullable|integer|exists:quiz_attempts,id',
        ]);

        $question = QuizQuestion::findOrFail($validated['question_id']);

        $attempt = null;

        if (! empty($validated['attempt_id'])) {
            $attempt = QuizAttempt::where('id', $validated['attempt_id'])
                ->where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->first();
        }

        if (! $attempt) {
            // Fallback lang ito para sa mga lumang cached frontend na hindi pa
            // nagpapasa ng attempt_id — dapat na dumaan dito ang mga bago.
            $mockPhase = MockBoardPhase::where('module_id', $module->id)->first();

            $attempt = QuizAttempt::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'mock_board_id' => $mockPhase?->mock_board_id,
                ],
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
        }

        $selected = trim($validated['selected_option']);
        $correct = trim($question->correct_option);
        $isCorrect = (strcasecmp($selected, $correct) === 0);

        QuizAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
            ['selected_option' => $selected, 'is_correct' => $isCorrect]
        );

        // I-update ang timestamp ng attempt bilang bakas ng huling activity —
        // ginagamit ito ng startAttempt() para malaman kung kailan huling
        // gumalaw ang estudyante (para sa 1-minute grace period sa pag-resume).
        $attempt->touch();

        return response()->json(['success' => true, 'isCorrect' => $isCorrect]);
    }

     public function submitQuiz(Request $request, Module $module)
    {
        $user = Auth::user();

        return DB::transaction(function () use ($user, $module, $request) {
            $mockPhase = MockBoardPhase::where('module_id', $module->id)->first();
            $mockBoardId = $mockPhase?->mock_board_id;
            $phaseType = $mockPhase?->phase_type;

            $attemptId = $request->input('attempt_id');
            $attempt = null;

            if (! empty($attemptId)) {
                $attempt = QuizAttempt::where('id', $attemptId)
                    ->where('user_id', $user->id)
                    ->where('module_id', $module->id)
                    ->first();
            }

            if (! $attempt) {
                // Fallback lang ito para sa mga lumang cached frontend na hindi pa
                // nagpapasa ng attempt_id.
                $attempt = QuizAttempt::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'module_id' => $module->id,
                        'mock_board_id' => $mockBoardId,
                    ],
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
            }

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

            // Bilang ng snapshot number base sa dati nang naitalang attempts,
            // hindi sa attempt_count ng QuizAttempt (para hindi ma-desync).
            $nextAttemptNumber = QuizAttemptSnapshot::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('mock_board_id', $mockBoardId)
                ->max('attempt_number');
            $nextAttemptNumber = ($nextAttemptNumber ?? 0) + 1;

            QuizAttemptSnapshot::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'mock_board_id' => $mockBoardId,
                'phase_type' => $phaseType,
                'attempt_number' => $nextAttemptNumber,
                'score' => $correctCount,
                'total' => $totalQuestions,
                'percentage' => $percentage,
                'passed' => $percentage >= ($module->passing_grade ?? 50),
                'started_at' => $attempt->started_at,
                'completed_at' => now(),
                'questions_snapshot' => $answers->map(function ($a) {
                    return [
                        'question_id' => $a->question_id,
                        'question_text' => $a->question->question_text ?? null,
                        'options' => $a->question->options ?? null,
                        'correct_option' => $a->question->correct_option ?? null,
                        'selected_option' => $a->selected_option,
                        'is_correct' => $a->is_correct,
                    ];
                })->values()->toArray(),
            ]);

            // SYNC TO MOCK BOARD ATTEMPTS (Para lumabas sa Teacher/Admin Analytics)
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
     * Return the authenticated user's attempt history for a module,
     * newest first. Summary fields only — no questions_snapshot,
     * so the collapsed list view stays light.
     */
    public function attemptHistory(Module $module)
    {
        $user = Auth::user();

        $snapshots = QuizAttemptSnapshot::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->orderByDesc('attempt_number')
            ->get([
                'id',
                'attempt_number',
                'score',
                'total',
                'percentage',
                'passed',
                'completed_at',
            ]);

        return response()->json([
            'success' => true,
            'attempts' => $snapshots,
        ]);
    }

    /**
     * Return the full per-question breakdown for a single attempt
     * snapshot, lazy-loaded when the student expands a row.
     */
    public function attemptSnapshotDetail(QuizAttemptSnapshot $snapshot)
    {
        if ($snapshot->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'attempt_number' => $snapshot->attempt_number,
            'score' => $snapshot->score,
            'total' => $snapshot->total,
            'percentage' => $snapshot->percentage,
            'passed' => $snapshot->passed,
            'completed_at' => $snapshot->completed_at,
            'questions' => $snapshot->questions_snapshot,
        ]);
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
