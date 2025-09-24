<?php

use App\Http\Controllers\AirtimeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\CableController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ElectricityController;
use App\Http\Controllers\MonnifyWebhookController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminServiceController;

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
// end of auth routes#


Route::post('/webhook/monnify', [MonnifyWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::middleware('auth')->group(function () {

Route::get('/dashboard', [Controller::class, 'dashboard'])->name('dashboard');
Route::get('/transactions', [Controller::class, 'transaction'])->name('transaction');
Route::post('/data/purchase', [DataController::class, 'purchase'])->name('data.purchase');
Route::post('/airtime/recharge', [AirtimeController::class, 'recharge'])->name('airtime.recharge');
Route::patch('/update-profile', [RegisterController::class, 'updateProfile'])->name('updateProfile.submit');

Route::post('/purchase/prepare', [CountryController::class, 'preparePurchase'])->name('prepare.purchase');
Route::get('/purchase/confirm', [CountryController::class, 'showConfirm'])->name('confirm.payment');
Route::post('/payments/process', [CountryController::class, 'process'])->name('payments.process');


Route::get('/data', [DataController::class, 'showData'])->name('data');
Route::get('/cabletv', [CableController::class, 'showCableTV'])->name('cabletv');
Route::post('/verify-smartcard', [CableController::class, 'verifySmartcard'])->name('verify.smartcard');
Route::post('/cabletv/purchase', [CableController::class, 'purchase'])->name('cabletv.purchase');
Route::post('/wallet/create-account', [WalletController::class, 'createAccount'])->name('wallets.createAccount');
Route::get('/profile', [Controller::class, 'profile'])->name('profile');
Route::get('/electricity', [ElectricityController::class, 'showElectricityPlans'])->name('electricity');
Route::post('/electricity/render-success', [ElectricityController::class, 'renderSuccessPartial'])->name('electricity.render-success');
Route::post('/verify-electricity-meter', [ElectricityController::class, 'verifyElectricityMeter'])->name('verify.meter');
Route::post('/electricity/purchase', [ElectricityController::class, 'purchase'])->name('electricity.purchase');

Route::get('/services', [CountryController::class, 'showServices'])->name('services.list');
Route::get('/services/{id}/countries', [CountryController::class, 'showCountries'])->name('services.countries');
Route::get('/services/{serviceId}/countries/load', [CountryController::class, 'showCountries']);
// The next step route (for when user selects a country)
Route::get('/services/{serviceId}/countries/{countryId}', [CountryController::class, 'showProducts'])->name('products.countries');
// Optional: API route for checking real-time availability
Route::get('/services/{serviceId}/countries/{countryId}/prices', [CountryController::class, 'getPrices'])
    ->name('countries.prices');
// When user clicks on a product, show country selection for that product
Route::get('/products/{id}/countries', [CountryController::class, 'showCountries'])->name('products.countries');
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
Route::get('/pin', function () {
    return view('users.pin');
});
Route::post('/update-pin', [Controller::class, 'updatePin'])->name('updateProfile.pin');
Route::get('/airtime', [AirtimeController::class, 'show'])->name('airtime.show');
// Add this to your routes/web.php file
Route::post('/render-success-partial', [AirtimeController::class, 'renderSuccessPartial'])
    ->name('render.success.partial')
    ->middleware('auth'); // Add auth middleware if needed


Route::get('/jambepin', [CableController::class, 'jambshow'])->name('jamb.show');

Route::get('/betting', [BettingController::class, 'show'])->name('bettinng.show');

Route::post('/fund-bet', [BettingController::class, 'fund_account'])->name('fund_bet');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});


// Route::get('/admin', function () {
//     return view('admin.home');
// });
Route::get('/admin/airtime', function () {
    return view('admin.airtime');
});
Route::get('/admin/jamb', function () {
    return view('admin.jamb');
});
Route::get('/admin/electricity', function () {
    return view('admin.electric');
});
Route::get('/admin/cabletv', function () {
    return view('admin.cabletv');
});
Route::get('/admin/social', function () {
    return view('admin.social');
});
Route::get('/admin/settings', function () {
    return view('admin.settings');
});
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login.form');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware(['admin'])->prefix('admin')->group(function () {
Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
Route::post('/users/{id}/update-password', [AdminController::class, 'updateUserPassword'])->name('admin.users.updatePassword');
Route::post('/users/{id}/update-pin', [AdminController::class, 'updateUserPin'])->name('admin.users.updatePin');
Route::post('/users/{user}/add-funds', [AdminController::class, 'addFunds'])->name('admin.users.add-funds');
Route::post('/users/{user}/deduct-funds', [AdminController::class, 'deductFunds'])->name('admin.users.deduct-funds');
Route::get('/services', [AdminServiceController::class, 'index'])->name('admin.services');
Route::get('/services/{service}/providers', [AdminServiceController::class, 'providers'])->name('admin.providers');
Route::get('/providers/{provider}/products', [AdminServiceController::class, 'products'])->name('admin.products');
Route::post('/products/{id}/update', [AdminServiceController::class, 'updateProduct'])->name('admin.products.update');
Route::post('/providers/{provider}/products/save', [AdminServiceController::class, 'saveProducts'])->name('admin.products.save');
Route::post('/providers/{provider}/markup', [AdminServiceController::class, 'applyMarkup'])->name('admin.providers.markup');
Route::delete('/products/{id}', [AdminServiceController::class, 'destroy'])->name('admin.products.delete');

Route::get('/list', [AdminAuthController::class, 'index'])->name('admin.list');
Route::get('/{admin}', [AdminAuthController::class, 'showadmin'])->name('admins.show');
Route::post('/{admin}/update', [AdminAuthController::class, 'updateadmin'])->name('admins.update');

});
Route::middleware(['admin:super_admin'])->prefix('admin')->group(function () {
    Route::post('/create', [AdminController::class, 'createAdmin'])->name('admin.create');
});

