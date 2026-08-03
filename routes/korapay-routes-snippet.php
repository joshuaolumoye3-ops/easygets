<?php

use App\Http\Controllers\Payments\KorapayCheckoutController;
use App\Http\Controllers\Payments\KorapayWebhookController;
use App\Http\Controllers\Payments\DedicatedAccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Korapay webhook — PUBLIC, no auth, no CSRF.
| Replaces: Route::post('/flutterwave/webhook', ...)
| Place this OUTSIDE the `auth` middleware group, same level as the old
| Flutterwave webhook route in your current web.php.
|--------------------------------------------------------------------------
*/
Route::post('/webhook/korapay', [KorapayWebhookController::class, 'handle'])
    ->name('korapay.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Everything below goes INSIDE the existing:
|   Route::middleware('auth')->group(function () { ... })
| block, alongside your other authenticated routes. Remove the old
| /virtual-account/create, /flutterwave/callback, /clear-virtual-account-session
| routes once you've fully migrated.
|--------------------------------------------------------------------------
*/

// One-time checkout
Route::post('/korapay/checkout', [KorapayCheckoutController::class, 'initiate'])
    ->middleware('check.user.not.blocked')
    ->name('korapay.checkout.initiate');

Route::get('/korapay/callback', [KorapayCheckoutController::class, 'callback'])
    ->name('korapay.callback');

// Dedicated (permanent) account — throttled: max 5 attempts/day per user,
// since this endpoint accepts BVN/NIN and should not be hammerable.
Route::post('/korapay/dedicated-account', [DedicatedAccountController::class, 'store'])
    ->middleware(['check.user.not.blocked', 'throttle:5,1440'])
    ->name('korapay.dedicated-account.store');
