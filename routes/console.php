<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Poll ALL pending OTP orders every minute (both 5sim and SMS-Bus)
// Saves SMS codes to DB and auto-refunds if provider says number expired
Schedule::command('otp:check-pending-orders')
    ->everyMinute()
    ->withoutOverlapping();

// Expire activations that have passed their expires_at time
// Cancels at provider and refunds user immediately
Schedule::command('activations:expire-otp')
    ->everyMinute()
    ->withoutOverlapping();

// Auto-cancel + refund any pending activation older than 3 minutes with no SMS
Schedule::command('activations:process-expired', ['--limit=100', '--timeout=3'])
    ->everyMinute()
    ->withoutOverlapping();