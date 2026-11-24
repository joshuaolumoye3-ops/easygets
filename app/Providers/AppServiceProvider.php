<?php

namespace App\Providers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // NEW: in production every generated address is https, whatever the proxy reports.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        Schema::defaultMorphKeyType('uuid');

        View::composer('partials.announcement', function ($view) {
            $announcement = Cache::remember('announcements:active:v1', now()->addSeconds(30), function () {
                try {
                    if (!Schema::hasTable('announcements')) {
                        return null;
                    }

                    return Announcement::where('is_active', true)
                        ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                        ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                        ->latest('created_at')
                        ->first();
                } catch (\Throwable $e) {
                    return null;
                }
            });

            $view->with('activeAnnouncement', $announcement);
        });

        View::composer('partials.admin.header', function ($view) {
            $announcements = Cache::remember('announcements:admin-list:v1', now()->addSeconds(30), function () {
                try {
                    return Schema::hasTable('announcements') ? Announcement::latest()->get() : collect();
                } catch (\Throwable $e) {
                    return collect();
                }
            });

            $view->with('adminAnnouncements', $announcements);
        });
    }
}