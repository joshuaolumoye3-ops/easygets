<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ── 1. Poll ALL pending orders every minute for OTP codes ──────────────
        // Handles both 5sim and SMS-Bus.
        // Automatically refunds if the provider says the number expired.
        $schedule->command('otp:check-pending-orders')
            ->everyMinute()
            ->withoutOverlapping()
            ->onFailure(fn () => Log::error('otp:check-pending-orders failed'));

        // ── 2. Expire activations that have passed their expires_at timestamp ──
        // Cancels at provider + refunds immediately.
        // Runs every minute so users get their money back as soon as the timer runs out.
        $schedule->command('activations:expire-otp')
            ->everyMinute()
            ->withoutOverlapping()
            ->onFailure(fn () => Log::error('activations:expire-otp failed'));

        // ── 3. Auto-cancel activations with no SMS after 3 minutes ─────────────
        // Safety net for numbers that never hit expires_at but are clearly stale.
        // --timeout=3  → processes activations older than 3 minutes
        // --limit=100  → cap per run to avoid long-running processes
        $schedule->command('activations:process-expired', ['--limit=100', '--timeout=3'])
            ->everyMinute()          // run every minute so nothing sits longer than ~4 min total
            ->withoutOverlapping()   // built-in lock prevents concurrent runs
            ->onFailure(fn () => Log::error('activations:process-expired failed'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}