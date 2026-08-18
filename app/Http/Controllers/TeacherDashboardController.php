<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(): View
    {
        $teacher = auth()->user();

        abort_unless($teacher !== null && $teacher->role === 'teacher', 403);

        $classes = ClassModel::query()
            ->where('created_by', $teacher->id)
            ->withCount([
                'users as students_count' => function ($query) {
                    $query->where('role', 'student');
                },
            ])
            ->latest()
            ->get();

        $classIds = $classes->pluck('id');

        $quizModuleIds = Module::query()
            ->whereIn('class_id', $classIds)
            ->where('is_quiz', true)
            ->pluck('id');

        $attemptedQuizModuleIds = QuizAttempt::query()
            ->whereIn('module_id', $quizModuleIds)
            ->distinct()
            ->pluck('module_id');

        $totalStudents = User::query()
            ->where('role', 'student')
            ->whereHas('classes', function ($query) use ($teacher) {
                $query->where('classes.created_by', $teacher->id);
            })
            ->count();

        $quizzesPending = $quizModuleIds->count() - $attemptedQuizModuleIds->count();

        $avgClassScore = (float) (QuizAttempt::query()
            ->whereHas('module', function ($query) use ($classIds) {
                $query->whereIn('class_id', $classIds);
            })
            ->avg('percentage') ?? 0.0);

        $avgPerClass = QuizAttempt::query()
            ->join('modules', 'quiz_attempts.module_id', '=', 'modules.id')
            ->whereIn('modules.class_id', $classIds)
            ->groupBy('modules.class_id')
            ->selectRaw('modules.class_id, AVG(quiz_attempts.percentage) as avg_score')
            ->pluck('avg_score', 'class_id');

        $classes = $classes->map(function ($class) use ($avgPerClass) {
            $class->avg_score = (float) ($avgPerClass[$class->id] ?? 0.0);

            return $class;
        });

        $studentJoinedEvents = DB::table('class_user')
            ->join('classes', 'classes.id', '=', 'class_user.class_id')
            ->join('users', 'users.id', '=', 'class_user.user_id')
            ->where('classes.created_by', $teacher->id)
            ->where('users.role', 'student')
            ->latest('class_user.joined_at')
            ->limit(8)
            ->get([
                'users.name as student_name',
                'classes.name as class_name',
                'class_user.joined_at as occurred_at',
            ])
            ->map(function ($event) {
                return [
                    'type' => 'student_joined',
                    'label' => $event->student_name.' joined '.$event->class_name,
                    'occurred_at' => $event->occurred_at ? Carbon::parse($event->occurred_at, 'Asia/Manila') : null,
                ];
            });

        $quizSubmittedEvents = QuizAttempt::query()
            ->join('modules', 'modules.id', '=', 'quiz_attempts.module_id')
            ->join('classes', 'classes.id', '=', 'modules.class_id')
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->where('classes.created_by', $teacher->id)
            ->latest('quiz_attempts.attempted_at')
            ->limit(8)
            ->get([
                'users.name as student_name',
                'modules.title as module_title',
                'quiz_attempts.attempted_at as occurred_at',
            ])
            ->map(function ($event) {
                return [
                    'type' => 'quiz_submitted',
                    'label' => $event->student_name.' submitted '.$event->module_title,
                    'occurred_at' => $event->occurred_at ? Carbon::parse($event->occurred_at, 'Asia/Manila') : null,
                ];
            });

        $moduleCompletedEvents = ModuleProgress::query()
            ->join('modules', 'modules.id', '=', 'module_progress.module_id')
            ->join('classes', 'classes.id', '=', 'modules.class_id')
            ->join('users', 'users.id', '=', 'module_progress.user_id')
            ->where('classes.created_by', $teacher->id)
            ->where('module_progress.completed', true)
            ->latest('module_progress.completed_at')
            ->limit(8)
            ->get([
                'users.name as student_name',
                'modules.title as module_title',
                'module_progress.completed_at as occurred_at',
            ])
            ->map(function ($event) {
                return [
                    'type' => 'module_completed',
                    'label' => $event->student_name.' completed '.$event->module_title,
                    'occurred_at' => $event->occurred_at ? Carbon::parse($event->occurred_at, 'Asia/Manila') : null,
                ];
            });

        $recentActivity = $studentJoinedEvents
            ->concat($quizSubmittedEvents)
            ->concat($moduleCompletedEvents)
            ->sortByDesc('occurred_at')
            ->take(6)
            ->values();

        $messages = $teacher->chats()
            ->with(['lastMessage', 'participants'])
            ->latest()
            ->limit(2)
            ->get()
            ->map(function ($chat) use ($teacher) {
                $other = $chat->participants->firstWhere('id', '!=', $teacher->id);
                $name = $other?->name ?? 'Unknown User';

                return [
                    'name' => $name,
                    'initials' => Str::of($name)
                        ->explode(' ')
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
                        ->implode(''),
                    'preview' => Str::limit($chat->lastMessage?->body ?? '', 60),
                ];
            });

        $announcements = Announcement::query()
            ->with('class')
            ->whereIn('class_id', $classIds)
            ->latest()
            ->limit(3)
            ->get();

        return view('pages.teacher.teacher', compact(
            'totalStudents',
            'quizzesPending',
            'avgClassScore',
            'classes',
            'recentActivity',
            'messages',
            'announcements'
        ));
    }
}
