<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTestBankQuestionsToModuleRequest;
use App\Http\Requests\StoreTestBankQuestionRequest;
use App\Http\Requests\UpdateTestBankQuestionRequest;
use App\Models\Module;
use App\Models\QuizQuestion;
use App\Models\TestBankQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestBankController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $editQuestion = $request->filled('edit')
            ? $this->visibleQuestionsQuery($user)->findOrFail($request->integer('edit'))
            : null;

        $questions = $this->visibleQuestionsQuery($user)
            ->with('creator')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search')->toString();

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery->where('question_text', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->string('difficulty')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('pages.teacher.test-bank.index', [
            'questions' => $questions,
            'modules' => $this->availableModules($user),
            'editQuestion' => $editQuestion,
        ]);
    }

    public function store(StoreTestBankQuestionRequest $request)
    {
        $data = $request->validated();
        $data['program'] = $request->user()->program;

        TestBankQuestion::query()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('test-bank.index')->with('success', 'Question saved to the Test Bank.');
    }

    public function update(UpdateTestBankQuestionRequest $request, TestBankQuestion $testBankQuestion)
    {
        $this->authorizeQuestion($request->user(), $testBankQuestion);
        $testBankQuestion->update($request->validated());

        return redirect()->route('test-bank.index')->with('success', 'Test Bank question updated.');
    }

    public function archive(Request $request, TestBankQuestion $testBankQuestion)
    {
        abort_unless(in_array($request->user()?->role, ['teacher', 'admin', 'superadmin'], true), 403);
        $this->authorizeQuestion($request->user(), $testBankQuestion);
        $testBankQuestion->update(['is_archived' => true]);

        return redirect()->route('test-bank.index')->with('success', 'Question archived from the Test Bank.');
    }

    public function addToModule(AddTestBankQuestionsToModuleRequest $request, Module $module)
    {
        $this->authorizeModule($request->user(), $module);

        $questionIds = $request->validated('test_bank_question_ids');
        $questions = $this->visibleQuestionsQuery($request->user())
            ->whereIn('id', $questionIds)
            ->where('status', 'approved')
            ->get();

        abort_if($questions->count() !== count(array_unique($questionIds)), 403);

        $addedCount = DB::transaction(function () use ($module, $questions): int {
            $existingSourceIds = $module->quizQuestions()
                ->whereNotNull('test_bank_question_id')
                ->pluck('test_bank_question_id')
                ->all();
            $nextOrder = ((int) $module->quizQuestions()->max('order')) + 1;
            $addedCount = 0;

            foreach ($questions as $question) {
                if (in_array($question->id, $existingSourceIds, true)) {
                    continue;
                }

                QuizQuestion::query()->create([
                    'module_id' => $module->id,
                    'test_bank_question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'options' => $question->options,
                    'correct_option' => $question->correct_option,
                    'points' => $question->points,
                    'order' => $nextOrder++,
                    'difficulty' => $question->difficulty,
                ]);
                $addedCount++;
            }

            return $addedCount;
        });

        return redirect()->route('quiz.create', $module)
            ->with('success', "{$addedCount} Test Bank question(s) added as assessment snapshots.");
    }

    public function importModuleQuestions(Request $request, Module $module)
    {
        abort_unless(in_array($request->user()?->role, ['teacher', 'admin', 'superadmin'], true), 403);
        $this->authorizeModule($request->user(), $module);

        $createdCount = DB::transaction(function () use ($module, $request): int {
            $questions = $module->quizQuestions()
                ->whereNull('test_bank_question_id')
                ->get();

            foreach ($questions as $question) {
                $testBankQuestion = TestBankQuestion::query()->create([
                    'created_by' => $request->user()->id,
                    'program' => $module->class?->program ?? $request->user()->program,
                    'question_text' => $question->question_text,
                    'options' => $question->options,
                    'correct_option' => $question->correct_option,
                    'points' => $question->points,
                    'difficulty' => $question->difficulty,
                    'status' => 'approved',
                ]);

                $question->update(['test_bank_question_id' => $testBankQuestion->id]);
            }

            return $questions->count();
        });

        return redirect()->route('test-bank.index')
            ->with('success', "{$createdCount} existing question(s) added to the Test Bank.");
    }
    public function questionsJson(Request $request)
{
    abort_unless(in_array($request->user()?->role, ['teacher', 'admin', 'superadmin'], true), 403);

    $questions = $this->visibleQuestionsQuery($request->user())
        ->where('status', 'approved')
        ->when($request->filled('search'), function ($query) use ($request) {
            $term = $request->string('search')->toString();
            $query->where('question_text', 'like', "%{$term}%");
        })
        ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->string('difficulty')))
        ->latest()
        ->limit(100)
        ->get([
            'id',
            'question_text',
            'options',
            'correct_option',
            'points',
            'difficulty',
        ]);

    return response()->json([
        'data' => $questions,
    ]);
}
    private function visibleQuestionsQuery($user)
    {
        return TestBankQuestion::query()
            ->where('is_archived', false)
            ->when(! in_array($user->role, ['admin', 'superadmin'], true), fn ($query) => $query->where('program', $user->program));
    }

    private function availableModules($user)
    {
        return Module::query()
            ->where('is_quiz', true)
            ->when(! in_array($user->role, ['admin', 'superadmin'], true), function ($query) use ($user) {
                $query->where(function ($moduleQuery) use ($user) {
                    $moduleQuery->where('created_by', $user->id)
                        ->orWhereHas('class', fn ($classQuery) => $classQuery->where('created_by', $user->id));
                });
            })
            ->orderBy('title')
            ->get(['id', 'title', 'assessment_purpose']);
    }

    private function authorizeQuestion($user, TestBankQuestion $testBankQuestion): void
    {
        abort_unless(
            in_array($user->role, ['admin', 'superadmin'], true) || $testBankQuestion->created_by === $user->id,
            403,
        );
    }

    private function authorizeModule($user, Module $module): void
    {
        $isOwner = $module->created_by === $user->id
            || $module->class?->created_by === $user->id;

        abort_unless(in_array($user->role, ['admin', 'superadmin'], true) || $isOwner, 403);
    }
}
