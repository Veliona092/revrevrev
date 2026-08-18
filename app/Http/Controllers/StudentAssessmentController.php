<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;

class StudentAssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function resolveLayout(): string
    {
        $user = Auth::user();
        $track = $user->role === 'student' ? ($user->program ?? 'accountancy') : $user->role;

        return match ($track) {
            'psych' => 'layouts.appPsych',
            'educ' => 'layouts.appEduc',
            'teacher' => 'layouts.appTeach',
            'admin', 'superadmin' => 'layouts.appAdmin',
            default => 'layouts.appAcc',
        };
    }

    public function index()
    {
        $user = Auth::user();
        $layout = $this->resolveLayout();

        $classIds = $user->classes()->pluck('classes.id');
        $classes = $user->classes()->orderBy('name')->get(['classes.id', 'classes.name']);

        $assessments = Module::query()
            ->whereIn('class_id', $classIds)
            ->where('is_formal_assessment', true)
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'all')
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('visibility', 'selected')
                            ->whereHas('visibleTo', fn ($q) => $q->where('users.id', $user->id));
                    })
                    ->orWhere(function ($sub) use ($user) {
                        $sub->where('visibility', 'except')
                            ->whereDoesntHave('visibleTo', fn ($q) => $q->where('users.id', $user->id));
                    });
            })
            ->with(['class', 'attemptGrants' => fn ($query) => $query->where('user_id', $user->id)])
            ->withCount('quizQuestions')
            ->orderBy('title')
            ->get()
            ->each(function (Module $module) use ($user) {
                $attempt = QuizAttempt::query()
                    ->where('user_id', $user->id)
                    ->where('module_id', $module->id)
                    ->orderByDesc('percentage')
                    ->first();

                $module->student_attempt = $attempt;
                $module->attempts_used = $attempt->attempt_count ?? 0;
                $module->attempts_allowed = $module->allowedAttemptsFor($user->id);
            });

        return view('pages.student.assessment', compact('assessments', 'classes', 'layout'));
    }

    public function take(Module $module)
    {
        $user = Auth::user();

        if (! $module->is_formal_assessment) {
            abort(404);
        }

        if (! $module->class->users()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not enrolled in this class.');
        }

        if ($module->isOverdue()) {
            abort(403, 'This assessment is past its due date and can no longer be taken.');
        }

        $questions = $module->quizQuestions()->orderBy('order')->get();

        $attempt = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();

        $attemptsUsed = $attempt->attempt_count ?? 0;
        $attemptsAllowed = $module->allowedAttemptsFor($user->id);
        $isResuming = $attempt !== null && $attempt->status === 'in_progress';

        return view('pages.student.assessment-take', compact(
            'module',
            'questions',
            'attemptsUsed',
            'attemptsAllowed',
            'isResuming',
        ));
    }
}
