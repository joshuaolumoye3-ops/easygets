<?php

use App\Http\Controllers\AirtimeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\CableController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DataController;
use App\Http\Controllers\MonnifyWebhookController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialController;

Route::middleware('guest')->group(function () {

Route::get('/', function () {
    return view('auth.login');
});
Route::get('/signup', [RegisterController::class, 'showRegisterForm'])->name('signup.form');

Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

Route::post('/verify-otp', [RegisterController::class, 'verifyOtp'])->name('otp.verify');

Route::get('/onboarding/email-otp/{user}', [RegisterController::class, 'showOtpForm'])
    ->name('onboarding.emailOtp');
Route::get('/emailverification', function () {
    return view('onboarding.emailOtp');
});


Route::get('/signin', [LoginController::class, 'showLoginForm'])->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

});
// end of auth routes
Route::post('/webhook/monnify', [MonnifyWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::middleware('auth')->group(function () {

Route::get('/dashboard', [Controller::class, 'dashboard'])->name('dashboard');
Route::get('/transactions', [Controller::class, 'transaction'])->name('transaction');
Route::post('/data/purchase', [DataController::class, 'purchase'])->name('data.purchase');
Route::post('/airtime/recharge', [AirtimeController::class, 'recharge'])->name('airtime.recharge');
Route::patch('/update-profile', [RegisterController::class, 'updateProfile'])->name('updateProfile.submit');


Route::get('/data', [DataController::class, 'showData'])->name('data');
Route::get('/cabletv', [CableController::class, 'showCableTV'])->name('cabletv');
Route::post('/verify-smartcard', [CableController::class, 'verifySmartcard'])->name('verify.smartcard');
Route::post('/cabletv/purchase', [CableController::class, 'purchase'])->name('cabletv.purchase');
Route::post('/wallet/create-account', [WalletController::class, 'createAccount'])->name('wallets.createAccount');
Route::get('/profile', [Controller::class, 'profile'])->name('profile');
Route::post('/jamb', [CableController::class, 'jamb'])->name('jamb');



Route::get('/social/services', [SocialController::class, 'services'])->name('social.services');
Route::post('/social/order', [SocialController::class, 'order'])->name('social.order');

Route::get('/history', function () {
    return view('users.history');
});
Route::get('/more-services', function () {
    return view('users.moreService');
});
Route::get('/personal-information', function () {
    return view('users.personal_info');
});
Route::get('/country-otp', function () {
    return view('users.country_otp');
});
Route::get('/airtime', function () {
    return view('users.airtime');
});
Route::get('/jambepin', function () {
    return view('users.jambPin');
});
Route::get('/betting', function () {
    return view('users.betting');
});
Route::post('/fund-bet', [BettingController::class, 'fund_account'])->name('fund_bet');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
