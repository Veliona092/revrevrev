<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    private const ADMIN_RESETTABLE_ROLES = ['student', 'teacher', 'psych', 'educ', 'accountancy'];

    private function actorCanManageTarget(User $actor, User $target): bool
    {
        if ($actor->role === 'superadmin') {
            return $target->role !== 'superadmin';
        }

        if ($actor->role === 'admin') {
            return in_array($target->role, self::ADMIN_RESETTABLE_ROLES, true);
        }

        return false;
    }

    private function sendStatusChangeEmail(User $user, bool $isActivation, ?string $reason = null): void
    {
        if (empty($user->email)) {
            return;
        }

        $subject = $isActivation
            ? 'Reviso - Your account has been activated'
            : 'Reviso - Your account has been deactivated';

        $body = $isActivation
            ? "
                <p>Hi {$user->name},</p>
                <p>Your Reviso account has been <strong>activated</strong>. You can now log in again.</p>
                <p>Login: <a href='".url('/login')."'>".url('/login').'</a></p>
            '
            : "
                <p>Hi {$user->name},</p>
                <p>Your Reviso account has been <strong>deactivated</strong> by an administrator.</p>
                <p><strong>Reason:</strong> ".($reason ? e($reason) : 'No reason provided').'</p>
            ';

        try {
            app(GmailService::class)->send($user->email, $subject, $body);
        } catch (\Throwable $e) {
            \Log::warning('Status change email failed for '.$user->email.': '.$e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $actor = Auth::user();

        if (! in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $query = User::query()->where('role', '!=', 'superadmin');

        if ($actor->role === 'admin') {
            $query->whereIn('role', self::ADMIN_RESETTABLE_ROLES);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('idnumber', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('pages.admin.users', compact('users'));
    }

    public function exportUsersCsv(Request $request)
    {
        $actor = Auth::user();

        if (! in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $query = User::query()->where('role', '!=', 'superadmin');

        if ($actor->role === 'admin') {
            $query->whereIn('role', self::ADMIN_RESETTABLE_ROLES);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('idnumber', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->get(['id', 'idnumber', 'name', 'email', 'role', 'program', 'status', 'created_at']);

        $csv = collect([['ID', 'ID Number', 'Name', 'Email', 'Role', 'Program', 'Status', 'Joined']])
            ->concat($users->map(fn (User $user) => [
                $user->id,
                $user->idnumber ?? '',
                $user->name ?? '',
                $user->email ?? '',
                $user->role ?? '',
                $user->program ?? '',
                $user->status ?? 'pending',
                $user->created_at?->format('Y-m-d') ?? '',
            ]))
            ->map(fn (array $row) => implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row)))
            ->implode("\n");

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'users-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function resetPassword($id)
    {
        $actor = Auth::user();

        if (! in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $user = User::findOrFail($id);

        if (! $this->actorCanManageTarget($actor, $user)) {
            return redirect()->back()->withErrors([
                'error' => 'You are not allowed to reset this account password.',
            ]);
        }

        $newPassword = Str::password(
            length: 12,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false
        );
        $user->password = Hash::make($newPassword);
        $user->save();

        // Fixed: use idnumber instead of username
        return redirect()->back()->with('success', "New password for {$user->idnumber}: {$newPassword}");
    }

    public function toggleStatus(Request $request, $id)
    {
        $actor = Auth::user();

        if (! in_array($actor->role, ['admin', 'superadmin'], true)) {
            abort(403);
        }

        $user = User::findOrFail($id);

        if (! $this->actorCanManageTarget($actor, $user)) {
            return redirect()->back()->withErrors([
                'error' => 'You are not allowed to change this account status.',
            ]);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (($user->status ?? 'pending') === 'active') {
            $reason = trim((string) ($validated['reason'] ?? ''));
            $reason = $reason !== '' ? $reason : null;

            $user->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $this->sendStatusChangeEmail($user, false, $reason);

            return redirect()->back()->with('status', "{$user->idnumber} has been deactivated.");
        }

        $user->update([
            'status' => 'active',
            'rejection_reason' => null,
        ]);

        $this->sendStatusChangeEmail($user, true);

        return redirect()->back()->with('status', "{$user->idnumber} has been activated.");
    }
}
