<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();
        $track = $user->role === 'student' ? ($user->program ?? 'accountancy') : $user->role;
        $layout = match ($track) {
            'psych' => 'layouts.appPsych',
            'educ' => 'layouts.appEduc',
            'teacher' => 'layouts.appTeach',
            'admin', 'superadmin' => 'layouts.appAdmin',
            default => 'layouts.appAcc',
        };

        return view('pages.student.progress', compact('layout'));
    }
}
