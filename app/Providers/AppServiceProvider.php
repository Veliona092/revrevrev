<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\URL::forceScheme('https');

        Gate::define('view-batch-analytics', function ($user): bool {
            return in_array($user->role, ['teacher', 'admin', 'superadmin'], true);
        });

        View::composer([
            'layouts.appTeach',
            'layouts.appPsych',
            'layouts.appEduc',
            'layouts.appAcc',
            'layouts.app',
        ], function ($view) {
            if (! Auth::check()) {
                $view->with('announcementUnreadCount', 0);

                return;
            }

            $user = Auth::user();

            if (in_array($user->role, ['admin', 'superadmin'], true)) {
                $classIds = ClassModel::query()->pluck('id');
            } elseif ($user->role === 'teacher') {
                $classIds = ClassModel::query()
                    ->where('created_by', $user->id)
                    ->pluck('id');
            } else {
                $classIds = $user->classes()->pluck('classes.id');
            }

            if ($classIds->isEmpty()) {
                $view->with('announcementUnreadCount', 0);

                return;
            }

            $count = Announcement::query()
                ->whereIn('class_id', $classIds)
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')
                        ->from('announcement_reads')
                        ->whereColumn('announcement_reads.announcement_id', 'announcements.id')
                        ->where('announcement_reads.user_id', $user->id);
                })
                ->count();

            $view->with('announcementUnreadCount', $count);
        });
    }
}
