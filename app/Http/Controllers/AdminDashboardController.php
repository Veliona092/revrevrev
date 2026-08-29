<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\MockBoardStatisticsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private MockBoardStatisticsService $statisticsService
    ) {}

    public function index(): View
    {
        $actor = Auth::user();
        abort_unless($actor !== null && in_array($actor->role, ['admin', 'superadmin'], true), 403);

        $totalUsers = User::query()->count();
        $pendingApprovals = User::query()->where('status', 'pending')->count();
        $totalActiveClasses = ClassModel::query()->count();
        $totalQuizAttempts = QuizAttempt::query()->count();

        $pendingUsers = User::query()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(5)
            ->get(['id', 'idnumber', 'name', 'email', 'role', 'created_at']);

        $roleDistribution = User::query()
            ->where('status', 'active')
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->orderBy('role')
            ->get()
            ->map(function ($row) {
                return [
                    'role' => (string) $row->role,
                    'total' => (int) $row->total,
                ];
            });

        $roleStatusCounts = User::query()
            ->select('role', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('role', 'status')
            ->get();

        $roleBreakdown = $roleStatusCounts
            ->groupBy('role')
            ->map(function (Collection $rows, string $role) {
                return [
                    'role' => $role,
                    'active' => (int) ($rows->firstWhere('status', 'active')->total ?? 0),
                    'pending' => (int) ($rows->firstWhere('status', 'pending')->total ?? 0),
                    'rejected' => (int) ($rows->firstWhere('status', 'rejected')->total ?? 0),
                ];
            })
            ->values();

        // Quick student/teacher totals (excluding superadmin/admin)
        $totalStudents = User::query()
            ->where('status', 'active')
            ->where('role', 'student')
            ->count();

        $totalTeachers = User::query()
            ->where('status', 'active')
            ->where('role', 'teacher')
            ->count();

        // Breakdown per class — students enrolled per class (With Year Level)
        $classesBreakdown = ClassModel::query()
            ->withCount([
                'users as students_count' => function ($query) {
                    $query->where('role', 'student');
                },
            ])
            ->orderByDesc('school_year')
            ->orderBy('name')
            ->get(['id', 'name', 'school_year', 'year_level', 'created_by']) // Idinagdag ang 'year_level'
            ->map(function (ClassModel $class) {
                return [
                    'name' => $class->name,
                    'school_year' => $class->school_year,
                    'year_level' => $class->year_level, // Idinagdag sa array
                    'student_count' => (int) $class->students_count,
                ];
            });

        // Breakdown per program
        $programEnrollment = User::query()
            ->where('status', 'active')
            ->where('role', 'student')
            ->whereNotNull('program')
            ->select('program', DB::raw('COUNT(*) as total'))
            ->groupBy('program')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'program' => (string) $row->program,
                    'total' => (int) $row->total,
                ];
            });

        // Failed students count per class
        $failedStudentsByClassSection = DB::table('mock_board_attempts')
            ->join('users', 'users.id', '=', 'mock_board_attempts.user_id')
            ->join('class_user', 'class_user.user_id', '=', 'users.id')
            ->join('classes', 'classes.id', '=', 'class_user.class_id')
            ->where('mock_board_attempts.passed', false)
            ->select(
                'classes.name as class_name',
                DB::raw('COUNT(DISTINCT users.id) as failed_count')
            )
            ->groupBy('classes.id', 'classes.name')
            ->orderByDesc('failed_count')
            ->get();

        $signupEvents = User::query()
            ->latest('created_at')
            ->limit(20)
            ->get(['name', 'role', 'created_at'])
            ->map(function (User $user) {
                return [
                    'type' => 'signup',
                    'dot' => 'amber',
                    'label' => trim(($user->name ?? 'A user').' signed up as '.($user->role ?? 'unknown')),
                    'occurred_at' => $user->created_at,
                ];
            });

        $approvalEvents = User::query()
            ->where('status', 'active')
            ->whereColumn('updated_at', '>', 'created_at')
            ->latest('updated_at')
            ->limit(20)
            ->get(['name', 'updated_at'])
            ->map(function (User $user) {
                return [
                    'type' => 'approval',
                    'dot' => 'green',
                    'label' => trim(($user->name ?? 'A user').' was approved'),
                    'occurred_at' => $user->updated_at,
                ];
            });

        $classEvents = ClassModel::query()
            ->with('creator:id,name')
            ->latest('created_at')
            ->limit(20)
            ->get(['id', 'name', 'created_by', 'created_at'])
            ->map(function (ClassModel $class) {
                return [
                    'type' => 'class_created',
                    'dot' => 'blue',
                    'label' => trim(($class->creator?->name ?? 'Unknown').' created class '.$class->name),
                    'occurred_at' => $class->created_at,
                ];
            });

        $quizEvents = QuizAttempt::query()
            ->with(['user:id,name', 'module:id,class_id,title', 'module.class:id,name'])
            ->latest('attempted_at')
            ->limit(20)
            ->get()
            ->map(function (QuizAttempt $attempt) {
                $actorName = $attempt->user?->name ?? 'A student';
                $className = $attempt->module?->class?->name;
                $suffix = $className ? ' in '.$className : '';

                return [
                    'type' => 'quiz_milestone',
                    'dot' => 'purple',
                    'label' => $actorName.' submitted a quiz'.$suffix,
                    'occurred_at' => $attempt->attempted_at ?? $attempt->created_at,
                ];
            });

        $platformActivity = $signupEvents
            ->concat($approvalEvents)
            ->concat($classEvents)
            ->concat($quizEvents)
            ->sortByDesc('occurred_at')
            ->take(10)
            ->values();

        $recentClasses = ClassModel::query()
            ->with('creator:id,name')
            ->withCount([
                'users as students_count' => function ($query) {
                    $query->where('role', 'student');
                },
            ])
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'name', 'created_by', 'created_at']);

        // Failed students count per program (lahat ng program ay lilitaw, 0 kung wala)
        $failedStudentsByProgram = User::query()
            ->where('status', 'active')
            ->where('role', 'student')
            ->whereNotNull('program')
            ->select('program')
            ->distinct()
            ->get()
            ->map(function ($user) {
                $prog = $user->program;

                $failedCount = DB::table('mock_board_attempts')
                    ->join('users', 'users.id', '=', 'mock_board_attempts.user_id')
                    ->where('mock_board_attempts.passed', false)
                    ->where('users.program', $prog)
                    ->distinct('users.id')
                    ->count('users.id');

                return (object) [
                    'program' => $prog,
                    'failed_count' => $failedCount,
                ];
            })
            ->sortByDesc('failed_count')
            ->values();

        // Forecast: projected pass rate per mock board, grouped by program.
        // Lahat ng mock boards na may attempts na ang isasama (iba-ibang teacher).
        $examLabels = [
            'accountancy' => 'Accountancy (CPALE)',
            'education' => 'Education (LEPT)',
            'psychology' => 'Psychology (RPsy Board Exam)',
        ];

        $forecastByProgram = [];

        foreach ($examLabels as $programKey => $label) {
            $variants = match ($programKey) {
                'education' => ['education', 'educ'],
                'psychology' => ['psychology', 'psych'],
                default => [$programKey],
            };

            $boardsForProgram = MockBoard::whereIn('program', $variants)
                ->orderByDesc('created_at')
                ->get();

            $boardEntries = [];
            foreach ($boardsForProgram as $board) {
                $forecast = $this->statisticsService->computeForecastedPassRate($board, $programKey);

                if (($forecast['sample_size'] ?? 0) === 0) {
                    continue;
                }

                $boardEntries[] = [
                    'title' => $board->title,
                    'projected_pass_rate' => $forecast['projected_batch_pass_rate'] ?? 0,
                    'sample_size' => $forecast['sample_size'] ?? 0,
                    'batch_average' => $forecast['batch_average'] ?? 0,
                ];
            }

            if (! empty($boardEntries)) {
                $forecastByProgram[$programKey] = [
                    'exam_label' => $label,
                    'boards' => $boardEntries,
                ];
            }
        }

        $postTestAnalytics = $this->statisticsService->getAdminPostTestAnalytics();

        return view('pages.admin.admin', compact(
            'totalUsers',
            'pendingApprovals',
            'totalActiveClasses',
            'totalQuizAttempts',
            'pendingUsers',
            'roleDistribution',
            'roleBreakdown',
            'platformActivity',
            'recentClasses',
            'totalStudents',
            'totalTeachers',
            'classesBreakdown',
            'programEnrollment',
            'failedStudentsByClassSection',
            'failedStudentsByProgram',
            'forecastByProgram',
            'postTestAnalytics'
        ));
    }
}
