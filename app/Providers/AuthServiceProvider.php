<?php

namespace App\Providers;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // ...
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-global-ai-settings', function (User $user): bool {
            return $user->role === 'superadmin';
        });

        Gate::define('manage-class-ai-settings', function (User $user, ClassModel $class): bool {
            return $user->role === 'superadmin' || $user->role === 'admin';
        });
        Gate::define('manage-mock-board', function (User $user, MockBoard $mockBoard): bool {
            return $user->id === $mockBoard->teacher_id
                || in_array($user->role, ['admin', 'superadmin']);
        });

        Gate::define('view-mock-board', function (User $user, MockBoard $mockBoard): bool {
            if (in_array($user->role, ['admin', 'superadmin'])) {
                return true;
            }

            if ($user->role === 'teacher') {
                return $user->id === $mockBoard->teacher_id;
            }

            if (in_array($user->role, ['student', 'psych', 'educ', 'accountancy'], true)) {
                $studentProgram = strtolower(trim($user->program ?? ''));
                $boardProgram = strtolower(trim($mockBoard->program ?? ''));

                return $mockBoard->isApproved() && $studentProgram !== '' && $studentProgram === $boardProgram;
            }

            return false;
        });

        Gate::define('view-batch-analytics', function (User $user): bool {
            // TEMPORARY: Allow all authenticated users for testing
            return true;
        });

        // Use custom notification with Gmail API
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new ResetPasswordNotification($token))->toMail($notifiable);
        });
    }
}
