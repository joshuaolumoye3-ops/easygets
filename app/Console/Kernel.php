<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Schedule the OTP activation expiry command to run every minute
        $schedule->command('activations:expire-otp')->everyMinute();
        // Schedule OTP pending order status check every minute
        $schedule->command('otp:check-pending-orders')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
