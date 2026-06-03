<?php

namespace App\Providers;

use App\Models\Announcement;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

        // Force utf8mb4 with older collation
        Schema::defaultStringLength(191);
        Schema::defaultMorphKeyType('uuid');

        if (Schema::hasTable('announcements')) {
            try {
                $activeAnnouncement = Announcement::where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($query) {
                        $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    })
                    ->latest('created_at')
                    ->first();

                View::share('activeAnnouncement', $activeAnnouncement);
                View::composer('partials.admin.header', function ($view) {
                    $view->with('adminAnnouncements', Announcement::latest()->get());
                });
            } catch (\Throwable $e) {
                // Silently ignore until migration is run or table is unavailable.
            }
        }
        
        // Create storage symlink if it doesn't exist (for public file access)
        $this->createStorageLink();
    }
    
    /**
     * Create storage symbolic link for public file access
     */
    private function createStorageLink(): void
    {
        $target = public_path('storage');
        $link = storage_path('app/public');
        
        // Check if symlink already exists
        if (!file_exists($target)) {
            try {
                // For Windows, use junction; for Unix, use symlink
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows - use exec to create junction
                    $command = "mklink /J \"" . str_replace('/', '\\', $target) . "\" \"" . str_replace('/', '\\', $link) . "\"";
                    exec($command, $output, $returnVar);
                    if ($returnVar === 0) {
                        \Illuminate\Support\Facades\Log::info('Storage junction created successfully');
                    }
                } else {
                    // Unix/Linux/Mac - use symlink
                    symlink($link, $target);
                    \Illuminate\Support\Facades\Log::info('Storage symlink created successfully');
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Storage link creation failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
