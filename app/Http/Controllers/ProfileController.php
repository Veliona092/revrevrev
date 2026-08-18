<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Programs list — single source of truth
    const PROGRAMS = [
        'psych' => 'Psychology',
        'educ' => 'Education',
        'accountancy' => 'Accountancy',
    ];

    public function show()
    {
        return view('pages.teacher.profile', [
            'user' => Auth::user(),
            'programs' => self::PROGRAMS,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', function ($attr, $val, $fail) {
                if (! Hash::check($val, Auth::user()->password)) {
                    $fail('Current password is incorrect.');
                }
            }],
            'password' => 'required|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('profile')->with('status', 'Password updated successfully.');
    }

    public function updateEmail(Request $request)
    {
        $otp = rand(100000, 999999);

        session()->forget([
            'pending_email',
            'email_otp',
            'email_otp_exp',
            'current_email_otp',
            'current_email_otp_exp',
            'new_email_otp',
            'new_email_otp_exp',
            'email_change_stage',
        ]);

        session([
            'email_change_stage' => 'verify_current',
            'current_email_otp' => $otp,
            'current_email_otp_exp' => now()->addMinutes(10),
        ]);

        // Send OTP via GmailService
        try {
            app(GmailService::class)->sendMail(
                Auth::user()->email,
                'Reviso — Verify Current Email',
                "We received a request to change your account email. Your verification code is: <strong>{$otp}</strong>. It expires in 10 minutes."
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'current_email_otp' => 'Unable to send verification code right now. Gmail authorization may be expired. Please reconnect Gmail and try again.',
            ]);
        }

        return redirect()->route('profile')
            ->with('status', 'Verification code sent to your current email address.');
    }

    public function requestNewEmailVerification(Request $request)
    {
        $stage = (string) session('email_change_stage', '');

        if (! in_array($stage, ['enter_new', 'verify_new'], true)) {
            return back()->withErrors([
                'new_email' => 'Please verify your current email first.',
            ]);
        }

        $validated = $request->validate([
            'new_email' => 'required|email|unique:users,email,'.Auth::id(),
        ]);

        $otp = rand(100000, 999999);

        session([
            'email_change_stage' => 'verify_new',
            'pending_email' => $validated['new_email'],
            'new_email_otp' => $otp,
            'new_email_otp_exp' => now()->addMinutes(10),
        ]);

        try {
            app(GmailService::class)->sendMail(
                $validated['new_email'],
                'Reviso — Verify New Email',
                "Your verification code for your new email is: <strong>{$otp}</strong>. It expires in 10 minutes."
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'new_email' => 'Unable to send verification code to your new email right now. Please try again.',
            ]);
        }

        return redirect()->route('profile')
            ->with('status', 'Verification code sent to '.$validated['new_email'].'.');
    }

    public function verifyEmailChange(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $stage = (string) session('email_change_stage', '');

        if ($stage === 'verify_current') {
            if (
                session('current_email_otp') != $request->otp ||
                now()->isAfter(session('current_email_otp_exp'))
            ) {
                return back()->withErrors(['current_email_otp' => 'Invalid or expired verification code for your current email.']);
            }

            session()->forget(['current_email_otp', 'current_email_otp_exp']);
            session(['email_change_stage' => 'enter_new']);

            return redirect()->route('profile')->with('status', 'Current email verified. You can now enter your new email.');
        }

        if ($stage !== 'verify_new') {
            return back()->withErrors(['current_email_otp' => 'Please start email verification from your current email first.']);
        }

        if (
            session('new_email_otp') != $request->otp ||
            now()->isAfter(session('new_email_otp_exp'))
        ) {
            return back()->withErrors(['new_email_otp' => 'Invalid or expired verification code for your new email.']);
        }

        Auth::user()->update(['email' => session('pending_email')]);

        session()->forget([
            'pending_email',
            'email_otp',
            'email_otp_exp',
            'current_email_otp',
            'current_email_otp_exp',
            'new_email_otp',
            'new_email_otp_exp',
            'email_change_stage',
        ]);

        return redirect()->route('profile')->with('status', 'Email updated successfully.');
    }

    /**
     * Set program — locks on save, cannot be changed without admin unlock.
     */
    public function updateProgram(Request $request)
    {
        $user = Auth::user();

        // If already locked, block the request
        if ($user->program_locked) {
            return redirect()->route('profile')
                ->with('error', 'Your program is locked. Contact an admin to request a change.');
        }

        $validated = $request->validate([
            'program' => ['required', 'string', Rule::in(array_keys(self::PROGRAMS))],
        ]);

        $user->update([
            'program' => $validated['program'],
            'program_locked' => true, // lock immediately on save
        ]);

        return redirect()->route('profile')
            ->with('status', 'Program set to "'.self::PROGRAMS[$validated['program']].'". This cannot be changed without admin approval.');
    }

    /**
     * Admin unlocks a user's program so they can change it.
     */
    public function unlockProgram(Request $request, User $user)
    {
        // Must be admin
        if (! in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $user->update([
            'program' => null,
            'program_locked' => false,
        ]);

        return redirect()->back()
            ->with('status', $user->name.'\'s program has been unlocked.');
    }
}
