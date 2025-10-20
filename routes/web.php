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
use App\Http\Controllers\AdminEsimController;
use App\Http\Controllers\AdminServiceController;
use App\Http\Controllers\EsimController;
use App\Http\Controllers\FlutterwaveWebhookController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\VirtualAccountController;
use Illuminate\Support\Facades\Http;

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

Route::post('/flutterwave/webhook', [FlutterwaveWebhookController::class, 'handle'])
  ->name('flutterwave.webhook');

Route::middleware('auth')->group(function () {
    Route::post('/virtual-account/create', [VirtualAccountController::class, 'createAccount'])
          ->name('virtual-account.create');
    Route::post('/clear-virtual-account-session', [VirtualAccountController::class, 'clearSession'])->name('virtual-account.clear-session');

    Route::get('/dashboard', [Controller::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [Controller::class, 'transaction'])->name('transaction');
    Route::post('/data/purchase', [DataController::class, 'purchase'])->name('data.purchase');
    Route::post('/airtime/recharge', [AirtimeController::class, 'recharge'])->name('airtime.recharge');
    Route::patch('/update-profile', [RegisterController::class, 'updateProfile'])->name('updateProfile.submit');

    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::post('/history/get-sms', [HistoryController::class, 'getSmsCode'])->name('history.get-sms');
    Route::post('/history/cancel-activation', [HistoryController::class, 'cancelActivation'])->name('history.cancel-activation');

    Route::post('/purchase/prepare', [CountryController::class, 'preparePurchase'])->name('prepare.purchase');
    Route::get('/purchase/confirm', [CountryController::class, 'showConfirm'])->name('confirm.payment');
    Route::post('/payments/process', [CountryController::class, 'process'])->name('payments.process');
    Route::get('/esim', [EsimController::class, 'index'])->name('esim');
    Route::get('/countries/{code}/plan', [EsimController::class, 'plan'])->name('countries.plan');
    Route::get('/checkout/{code}/{packageId}', [EsimController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/{country}/{package}', [EsimController::class, 'store'])->name('checkout.process');
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

    // Services list
Route::get('/services', [CountryController::class, 'showServices'])->name('services.list');

// ==================== 5SIM PROVIDER ROUTES ====================
// Step 1: Show countries for a service
Route::get('/services/{serviceCode}/5sim/countries', [CountryController::class, 'showCountries5sim'])
    ->name('services.5sim.countries');

// Step 2: Show operators for a country
Route::get('/services/{serviceCode}/5sim/{country}/operators', [CountryController::class, 'showOperators5sim'])
    ->name('services.5sim.operators');

// Step 3: Prepare purchase (creates session)
Route::post('/prepare-purchase/5sim', [CountryController::class, 'preparePurchase5sim'])
    ->name('prepare.purchase.5sim')
    ->middleware('auth');

// ==================== SMS-BUS PROVIDER ROUTES ====================
// Step 1: Show countries for a service
Route::get('/services/{serviceId}/countries', [CountryController::class, 'showCountries'])
    ->name('services.countries');

// For AJAX loading (optional)
Route::get('/services/{serviceId}/countries/load', [CountryController::class, 'showCountries']);

// Step 2: Get prices for a country
Route::get('/services/{serviceId}/countries/{countryId}/prices', [CountryController::class, 'getPrices'])
    ->name('countries.prices');

// Step 3: Prepare purchase (creates session)
Route::post('/prepare-purchase', [CountryController::class, 'preparePurchase'])
    ->name('prepare.purchase')
    ->middleware('auth');

// ==================== COMMON ROUTES (BOTH PROVIDERS) ====================
// Payment confirmation page
Route::get('/confirm-payment', [CountryController::class, 'showConfirm'])
    ->name('confirm.payment')
    ->middleware('auth');

// Process payment (actual purchase)
Route::post('/process-payment', [CountryController::class, 'process'])
    ->name('process.payment')
    ->middleware('auth');
    // When user clicks on a product, show country selection for that product
    Route::get('/products/{id}/countries', [CountryController::class, 'showCountries'])->name('products.countries');
    Route::post('/jamb', [CableController::class, 'jamb'])->name('jamb');



    Route::get('/social/services', [SocialController::class, 'services'])->name('social.services');
    Route::post('/social/order', [SocialController::class, 'order'])->name('social.order');

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
    Route::get('/esim-transaction', [AdminServiceController::class, 'esimindex'])->name('admin.esim.transaction');
    Route::get('/esim/{id}', [AdminServiceController::class, 'show'])->name('admin.esim.show');
    Route::post('/esim/upload', [AdminServiceController::class, 'upload'])->name('admin.esim.upload');
    
    Route::prefix('esim')->group(function () {
        Route::get('/', [AdminEsimController::class, 'index'])->name('admin.esim.index');
        Route::post('/refresh-countries', [AdminEsimController::class, 'refreshCountries'])->name('admin.esim.refresh');
        Route::post('/seed-countries', [AdminEsimController::class, 'seedSampleCountries'])->name('admin.esim.seed');
        Route::post('/{id}/update-price', [AdminEsimController::class, 'updatePrice'])->name('admin.esim.updatePrice');
        Route::get('/{countryId}/packages', [AdminEsimController::class, 'packages'])->name('admin.esim.packages');
        Route::post('/{countryId}/packages/save', [AdminEsimController::class, 'savePackage'])->name('admin.esim.savePackage');
        Route::delete('/packages/{id}', [AdminEsimController::class, 'deletePackage'])->name('admin.esim.deletePackage');
        Route::post('/{id}/toggle-status', [AdminEsimController::class, 'toggleCountryStatus'])->name('admin.esim.toggleStatus');
        Route::get('/transaction/{id}', [AdminEsimController::class, 'showTransaction'])->name('admin.esim.transaction.show');
    });


    Route::get('/list', [AdminAuthController::class, 'index'])->name('admin.list');
    Route::get('/{admin}', [AdminAuthController::class, 'showadmin'])->name('admins.show');
    Route::post('/{admin}/update', [AdminAuthController::class, 'updateadmin'])->name('admins.update');

});
Route::middleware(['admin:super_admin'])->prefix('admin')->group(function () {
    Route::post('/create', [AdminController::class, 'createAdmin'])->name('admin.create');
});

