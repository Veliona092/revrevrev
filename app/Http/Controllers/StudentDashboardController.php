<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Module;
use App\Models\QuizAttempt;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $enrolledClassIds = $user->classes()->pluck('classes.id');

        // ── Stat cards ──────────────────────────────────────────────
        $enrolledClasses = $user->classes()->count();

        $attemptedModuleIds = QuizAttempt::where('user_id', $user->id)->pluck('module_id');

        $pendingAssignments = Module::whereIn('class_id', $enrolledClassIds)
            ->where('is_quiz', true)
            ->where('is_formal_assessment', true)
            ->whereNotIn('id', $attemptedModuleIds)
            ->count();

        $overallAvg = (int) round(
            QuizAttempt::where('user_id', $user->id)->avg('percentage') ?? 0
        );

        // ── Upcoming quizzes (replaces Today's Schedule) ─────────────
        $upcomingQuizzes = Module::with('class')
            ->whereIn('class_id', $enrolledClassIds)
            ->where('is_quiz', true)
            ->where('is_formal_assessment', false)
            ->whereNotIn('id', $attemptedModuleIds)
            ->latest()
            ->limit(3)
            ->get();
        $upcomingQuizzesCount = Module::whereIn('class_id', $enrolledClassIds)
            ->where('is_quiz', true)
            ->where('is_formal_assessment', false)
            ->whereNotIn('id', $attemptedModuleIds)
            ->count();

        // ── Assignments (formal assessments only) ────────────────────
        $pendingQuizModules = Module::with('class')
            ->whereIn('class_id', $enrolledClassIds)
            ->where('is_quiz', true)
            ->where('is_formal_assessment', true)
            ->whereNotIn('id', $attemptedModuleIds)
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(3)
            ->get();

        $gradedAttempts = QuizAttempt::with('module.class')
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->latest('attempted_at')
            ->limit(3)
            ->get();

        $submittedAttempts = QuizAttempt::with('module.class')
            ->where('user_id', $user->id)
            ->where('passed', false)
            ->latest('attempted_at')
            ->limit(3)
            ->get();

        $totalAssessments = $pendingQuizModules->count() + $gradedAttempts->count() + $submittedAttempts->count();

        // ── My Progress ───────────────────────────────────────────────
        $classProgress = $user->classes()->get()->map(function ($class) use ($user) {
            $quizModuleIds = $class->modules()
                ->where('is_quiz', true)
                ->where('is_formal_assessment', false)
                ->pluck('id');

            $avg = (int) round(
                QuizAttempt::where('user_id', $user->id)
                    ->whereIn('module_id', $quizModuleIds)
                    ->avg('percentage') ?? 0
            );

            $color = $avg >= 75 ? '#1d9e75' : ($avg >= 50 ? '#d97706' : '#e24b4a');
            $label = $avg >= 75 ? 'Strong' : ($avg >= 50 ? 'Improving' : 'Needs Work');

            return [
                'class' => $class,
                'avg' => $avg,
                'color' => $color,
                'label' => $label,
            ];
        });

        // ── Announcements ─────────────────────────────────────────────
        $announcements = Announcement::with(['class', 'user'])
            ->whereIn('class_id', $enrolledClassIds)
            ->latest()
            ->limit(3)
            ->get();

        // ── Messages ──────────────────────────────────────────────────
        $recentMessages = $user->chats()
            ->with(['lastMessage.sender', 'participants'])
            ->latest()
            ->limit(2)
            ->get()
            ->map(function ($chat) use ($user) {
                $other = $chat->participants->firstWhere('id', '!=', $user->id);

                return [
                    'other' => $other,
                    'preview' => $chat->lastMessage?->body,
                ];
            })
            ->filter(fn ($msg) => $msg['other'] !== null)
            ->values();

        return match ($user->program) {
            'psych' => view('pages.psych.psych', compact(
                'enrolledClasses', 'pendingAssignments', 'overallAvg',
                'upcomingQuizzes', 'upcomingQuizzesCount', 'pendingQuizModules',
                'gradedAttempts', 'submittedAttempts', 'totalAssessments',
                'classProgress', 'announcements', 'recentMessages'
            )),
            'educ' => view('pages.educ.educ', compact(
                'enrolledClasses', 'pendingAssignments', 'overallAvg',
                'upcomingQuizzes', 'upcomingQuizzesCount', 'pendingQuizModules',
                'gradedAttempts', 'submittedAttempts', 'totalAssessments',
                'classProgress', 'announcements', 'recentMessages'
            )),
            default => view('pages.accountancy.accountancy', compact(
                'enrolledClasses', 'pendingAssignments', 'overallAvg',
                'upcomingQuizzes', 'upcomingQuizzesCount', 'pendingQuizModules',
                'gradedAttempts', 'submittedAttempts', 'totalAssessments',
                'classProgress', 'announcements', 'recentMessages'
            )),
        };
    }
}
