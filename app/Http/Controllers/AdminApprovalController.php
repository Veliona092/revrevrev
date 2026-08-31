<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GmailService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminApprovalController extends Controller
{
    private const ROLES = ['student', 'teacher', 'admin'];

    private const PROGRAMS = ['educ', 'accountancy', 'psych'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function actorCanAssignAdmin(): bool
    {
        return Auth::user()->role === 'superadmin';
    }

    private function normalizeProgram(string $role, ?string $program): ?string
    {
        if ($role === 'admin') {
            return null;
        }

        return $program;
    }

    private function hasValidProgramForRole(string $role, ?string $program): bool
    {
        if ($role === 'student') {
            return in_array($program, self::PROGRAMS, true);
        }

        if ($role === 'teacher') {
            return in_array($program, self::PROGRAMS, true);
        }

        if ($role === 'admin') {
            return $program === null;
        }

        return false;
    }

    /**
     * Show the pending approvals queue.
     */
    public function index()
    {
        if (! in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $pending = User::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $recentlyActioned = User::whereIn('status', ['active', 'rejected'])
            ->where('role', '!=', 'superadmin')
            ->whereNotNull('updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return view('pages.admin.approvals', compact('pending', 'recentlyActioned'));
    }

    /**
     * Approve a single user.
     */
    public function approve(Request $request, User $user)
    {
        if (! in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:student,teacher,admin'],
            'program' => ['nullable', 'in:educ,accountancy,psych'],
        ]);

        if ($validated['role'] === 'admin' && ! $this->actorCanAssignAdmin()) {
            return redirect()->back()->withErrors(['error' => 'Only superadmin can assign Admin role.']);
        }

        $requestedProgram = $validated['program'] ?? null;

        if (! $this->hasValidProgramForRole($validated['role'], $requestedProgram)) {
            return redirect()->back()->withErrors(['error' => 'Invalid role/program combination.']);
        }

        $program = $this->normalizeProgram($validated['role'], $requestedProgram);

        $user->update([
            'status' => 'active',
            'role' => $validated['role'],
            'program' => $program,
            'program_locked' => $program !== null,
            'rejection_reason' => null,
        ]);

        // Send approval email
        try {
            app(GmailService::class)->sendMail(
                $user->email,
                'Reviso — Your account has been approved!',
                "
                <p>Hi {$user->name},</p>
                <p>Great news! Your Reviso account has been <strong>approved</strong> by an administrator.</p>
                <p>You can now log in at <a href='".url('/login')."'>".url('/login').'</a></p>
                <p>Welcome to Reviso!</p>
                '
            );
        } catch (\Throwable $e) {
            \Log::warning('Approval email failed: '.$e->getMessage());
        }

        return redirect()->back()->with('status', "{$user->name} has been approved.");
    }

    /**
     * Approve multiple users at once.
     */
    public function approveMany(Request $request)
    {
        if (! in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'roles' => 'required|array',
            'roles.*' => 'required|in:student,teacher,admin',
            'programs' => 'array',
            'programs.*' => 'nullable|in:educ,accountancy,psych',
        ], [
            'user_ids.required' => 'Please select at least one user to approve.',
            'roles.required' => 'Please assign a role for each selected user.',
            'roles.*.required' => 'Please select a role for all checked users before approving.',
            'roles.*.in' => 'One or more assigned roles are invalid.',
            'programs.*.in' => 'One or more assigned programs are invalid.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors([
                'error' => $validator->errors()->first(),
            ]);
        }

        $validated = $validator->validated();

        /** @var Collection<int, User> $users */
        $users = User::whereIn('id', $validated['user_ids'])
            ->where('status', 'pending')
            ->get();

        foreach ($users as $user) {
            $role = $validated['roles'][$user->id] ?? null;
            $requestedProgram = $validated['programs'][$user->id] ?? null;

            if (in_array($role, ['student', 'teacher'], true) && empty($requestedProgram)) {
                return redirect()->back()->withErrors([
                    'error' => "Please select a program for {$user->name} ({$user->idnumber}) before approving.",
                ]);
            }
        }

        $approvedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $role = $validated['roles'][$user->id] ?? null;

            if ($role === 'admin' && ! $this->actorCanAssignAdmin()) {
                $skippedCount++;

                continue;
            }

            $requestedProgram = $validated['programs'][$user->id] ?? null;

            if (! $this->hasValidProgramForRole($role, $requestedProgram)) {
                $skippedCount++;

                continue;
            }

            $program = $this->normalizeProgram($role, $requestedProgram);

            $user->update([
                'status' => 'active',
                'role' => $role,
                'program' => $program,
                'program_locked' => $program !== null,
                'rejection_reason' => null,
            ]);
            $approvedCount++;

            try {
                app(GmailService::class)->sendMail(
                    $user->email,
                    'Reviso — Your account has been approved!',
                    "
                    <p>Hi {$user->name},</p>
                    <p>Your Reviso account has been <strong>approved</strong>.</p>
                    <p>Log in at <a href='".url('/login')."'>".url('/login').'</a></p>
                    '
                );
            } catch (\Throwable $e) {
                \Log::warning('Approval email failed for '.$user->email.': '.$e->getMessage());
            }
        }

        $message = $approvedCount.' account(s) approved.';
        if ($skippedCount > 0) {
            $message .= ' '.$skippedCount.' skipped due to invalid role/program assignment.';
        }

        return redirect()->back()->with('status', $message);
    }

    /**
     * Approve ALL pending users.
     */
    public function approveAll()
    {
        if (! in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        return redirect()->back()->withErrors([
            'error' => 'Approve All is disabled. Assign role and program per user or use Approve Selected.',
        ]);
    }

    /**
     * Reject a single user with a reason.
     */
    public function reject(Request $request, User $user)
    {
        if (! in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        // Send rejection email
        try {
            app(GmailService::class)->sendMail(
                $user->email,
                'Reviso — Account Application Update',
                "
                <p>Hi {$user->name},</p>
                <p>Unfortunately, your Reviso account application was <strong>not approved</strong>.</p>
                <p><strong>Reason:</strong> {$validated['reason']}</p>
                <p>If you believe this is a mistake, please contact your administrator.</p>
                "
            );
        } catch (\Throwable $e) {
            \Log::warning('Rejection email failed: '.$e->getMessage());
        }

        return redirect()->back()
            ->with('status', "{$user->name}'s account has been rejected.");
    }
}
