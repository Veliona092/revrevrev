<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\LectureController;

Route::get('/admin/lectures', [AdminUserController::class, 'index'])->name('admin.lectures');

Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
Route::post('/admin/users/{id}/reset-password', [AdminUserController::class, 'resetPassword'])->name('admin.users.reset');

// Lecture CRUD routes
Route::get('/lectures', [LectureController::class, 'index'])->name('lectures');
Route::get('/teacher/create', [LectureController::class, 'create'])->name('lectures.create');
Route::post('/lectures', [LectureController::class, 'store'])->name('lectures.store');
Route::get('/lectures/{id}/edit', [LectureController::class, 'edit'])->name('lectures.edit');
Route::put('/lectures/{id}', [LectureController::class, 'update'])->name('lectures.update');
Route::delete('/lectures/{id}', [LectureController::class, 'destroy'])->name('lectures.destroy');

// Login
Route::get('/', function () {
    return view('login');     // resources/views/login.blade.php
})->name('login');

// Handle login POST request
Route::post('/login', function (Request $request) {
    $credentials = $request->only('idnumber', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // Redirect based on user role
        switch ($user->role) {
            case 'psych':
                return redirect('/psych-dashboard');
            case 'educ':
                return redirect('/educ-dashboard');
            case 'teacher':
                return redirect('/teacher-dashboard');
            case 'admin':
                return redirect('/admin-dashboard');
            default:
                return redirect('/accountancy-dashboard');
        }
    }

    return back()->withErrors(['idnumber' => 'Invalid credentials'])->withInput();
});

// Dashboard / Home
Route::get('/dashboard', function () {
    $user = Auth::user(); // use singular user()

    if (!$user) {
        return redirect('/'); // Redirect to login if not authenticated
    }

    // Redirect based on user role
    return match ($user->role) {
        'psych' => redirect('/psych-dashboard'),
        'educ' => redirect('/educ-dashboard'),
        'teacher' => redirect('/teacher-dashboard'),
        'admin' => redirect('/admin-dashboard'),
        'accountancy' => redirect('/accountancy-dashboard'),
        default => abort(403, 'Unauthorized'),
    };
})->name('dashboard');

// Dashboard routes for roles
Route::get('/psych-dashboard', function () {
    return view('pages.psych.psych');
})->name('psychDashboard');

Route::get('/educ-dashboard', function () {
    return view('pages.educ.educ');
})->name('educDashboard');

Route::get('/teacher-dashboard', function () {
    return view('pages.teacher.teacher');
})->name('teacherDashboard');

Route::get('/admin-dashboard', function () {
    return view('pages.admin.admin');
})->name('adminDashboard');

Route::get('/accountancy-dashboard', function () {
    return view('pages.accountancy.accountancy');
})->name('accountancyDashboard');

// Helper to resolve a page view by role with accountancy fallback
$resolveRoleView = function (string $pageView) {
    $user = Auth::user();
    $role = $user?->role ?? 'accountancy';
    $view = "pages.{$role}.{$pageView}";

    if (View::exists($view)) {
        return view($view);
    }

    $fallback = "pages.accountancy.{$pageView}";
    if (View::exists($fallback)) {
        return view($fallback);
    }

    abort(404);
};

// Explicit per-page routes that resolve to pages.{role}.{page} and fall back to pages.accountancy.{page}

// Assessment (role-dependent)
Route::get('/assessment', function () use ($resolveRoleView) {
    return $resolveRoleView('assessment');
})->name('assessment');

// Profile (role-dependent)
Route::get('/profile', function () use ($resolveRoleView) {
    return $resolveRoleView('profile');
})->name('profile');

// Tables (role-dependent)
Route::get('/tables', function () use ($resolveRoleView) {
    return $resolveRoleView('tables');
})->name('tables');

// Register (role-dependent)
Route::get('/register', function () use ($resolveRoleView) {
    return $resolveRoleView('register');
})->name('register');

// Assessment exams (role-dependent)
Route::get('/assessmentexams', function () use ($resolveRoleView) {
    return $resolveRoleView('assessmentexams');
})->name('assessmentexams');

// Routes for psych pages
Route::get('/psych', function () {
    return view('pages.psych.psych');
});
Route::get('/psych/assessment', function () {
    return view('pages.psych.assessment');
});
Route::get('/psych/assessmentexams', function () {
    return view('pages.psych.assessmentexams');
});
Route::get('/psych/lectures', function () {
    return view('pages.psych.lectures');
});
Route::get('/psych/map', function () {
    return view('pages.psych.map');
});
Route::get('/psych/profile', function () {
    return view('pages.psych.profile');
});
Route::get('/psych/register', function () {
    return view('pages.psych.register');
});
Route::get('/psych/tables', function () {
    return view('pages.psych.tables');
});
Route::get('/psych/upgrade', function () {
    return view('pages.psych.upgrade');
});

// Routes for educ pages
Route::get('/educ', function () {
    return view('pages.educ.educ');
});
Route::get('/educ/assessment', function () {
    return view('pages.educ.assessment');
});
Route::get('/educ/assessmentexams', function () {
    return view('pages.educ.assessmentexams');
});
Route::get('/educ/lectures', function () {
    return view('pages.educ.lectures');
});
Route::get('/educ/map', function () {
    return view('pages.educ.map');
});
Route::get('/educ/profile', function () {
    return view('pages.educ.profile');
});
Route::get('/educ/register', function () {
    return view('pages.educ.register');
});
Route::get('/educ/tables', function () {
    return view('pages.educ.tables');
});
Route::get('/educ/upgrade', function () {
    return view('pages.educ.upgrade');
});

// Routes for accountancy pages
Route::get('/accountancy', function () {
    return view('pages.accountancy.accountancy');
});
Route::get('/accountancy/assessment', function () {
    return view('pages.accountancy.assessment');
});
Route::get('/accountancy/assessmentexams', function () {
    return view('pages.accountancy.assessmentexams');
});
Route::get('/accountancy/lectures', function () {
    return view('pages.accountancy.lectures');
});
Route::get('/accountancy/map', function () {
    return view('pages.accountancy.map');
});
Route::get('/accountancy/profile', function () {
    return view('pages.accountancy.profile');
});
Route::get('/accountancy/register', function () {
    return view('pages.accountancy.register');
});
Route::get('/accountancy/tables', function () {
    return view('pages.accountancy.tables');
});
Route::get('/accountancy/upgrade', function () {
    return view('pages.accountancy.upgrade');
});

// Routes for teacher pages
Route::get('/teacher', function () {
    return view('pages.teacher.teacher');
});
Route::get('/teacher/assessment', function () {
    return view('pages.teacher.assessment');
});
Route::get('/teacher/assessmentexams', function () {
    return view('pages.teacher.assessmentexams');
});
Route::get('/teacher/lectures', function () {
    return view('pages.teacher.lectures');
});
Route::get('/teacher/map', function () {
    return view('pages.teacher.map');
});
Route::get('/teacher/profile', function () {
    return view('pages.teacher.profile');
});
Route::get('/teacher/register', function () {
    return view('pages.teacher.register');
});
Route::get('/teacher/tables', function () {
    return view('pages.teacher.tables');
});
Route::get('/teacher/upgrade', function () {
    return view('pages.teacher.upgrade');
});

// Routes for admin pages
Route::get('/admin', function () {
    return view('pages.admin.admin');
});
Route::get('/admin/assessment', function () {
    return view('pages.admin.assessment');
});
Route::get('/admin/assessmentexams', function () {
    return view('pages.admin.assessmentexams');
});
//Route::get('/admin/lectures', function () {
//    return view('pages.admin.lectures');
//});
Route::get('/admin/map', function () {
    return view('pages.admin.map');
});
Route::get('/admin/profile', function () {
    return view('pages.admin.profile');
});
Route::get('/admin/register', function () {
    return view('pages.admin.register');
});
Route::get