<?php

use App\Http\Controllers\AirtimeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\CableController;
use App\Http\Controllers\JambPinController;
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
use App\Http\Controllers\SocialBoostController;
use App\Http\Controllers\VirtualAccountController;
use App\Http\Controllers\AdminDashboard;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\OtpController;

Route::prefix('app')->group(function () {

// OTP (5sim) endpoints for web (monolith)
    Route::prefix('otp')->group(function () {
            Route::get('/check/{order_id}', [OtpController::class, 'check'])
                ->name('otp.check')
                ->withoutMiddleware([
                    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                    \Illuminate\Auth\Middleware\Authenticate::class,
                    \Illuminate\Session\Middleware\AuthenticateSession::class
                ]);
            Route::post('/cancel/{order_id}', [OtpController::class, 'cancel'])
                ->name('otp.cancel')
                ->withoutMiddleware([
                    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                    \Illuminate\Auth\Middleware\Authenticate::class,
                    \Illuminate\Session\Middleware\AuthenticateSession::class
                ]);
            Route::post('/rebuy/{product}/{number}', [OtpController::class, 'rebuy'])->name('otp.rebuy');
                });
Route::middleware('guest')->group(function () {

    Route::get('/', function () {
        return view('auth.login');
    });

    Route::get('/signup', [RegisterController::class, 'showRegisterForm'])->name('signup.form');

    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

    Route::post('/verify-otp', [RegisterController::class, 'verifyOtp'])->name('otp.verify');

    Route::get('/onboarding/email-otp/{user}', [RegisterController::class, 'showOtpForm'])
        ->name('onboarding.emailOtp');

    // Email verification routes (for login flow)
    Route::get('/verify-email/{user}', [LoginController::class, 'showVerifyEmailForm'])->name('verify.email.form');
    Route::post('/verify-email-submit', [LoginController::class, 'verifyEmailFromLogin'])->name('verify.email.submit');
    Route::post('/verify-email-resend', [LoginController::class, 'resendVerificationOtp'])->name('verify.email.resend');

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

    Route::get('/flutterwave/callback', [VirtualAccountController::class, 'handleCallback'])
        ->name('flutterwave.callback');

    Route::post('/clear-virtual-account-session', [VirtualAccountController::class, 'clearSession'])
        ->name('virtual-account.clear-session');

    Route::get('/dashboard', [Controller::class, 'dashboard'])->middleware('check.user.not.blocked')->name('dashboard');
    Route::get('/transactions', [Controller::class, 'transaction'])->middleware('check.user.not.blocked')->name('transaction');
    Route::post('/data/purchase', [DataController::class, 'purchase'])->middleware('check.user.not.blocked')->name('data.purchase');
    Route::post('/airtime/recharge', [AirtimeController::class, 'recharge'])->middleware('check.user.not.blocked')->name('airtime.recharge');
    Route::patch('/update-profile', [RegisterController::class, 'updateProfile'])->middleware('check.user.not.blocked')->name('updateProfile.submit');

    Route::get('/history', [HistoryController::class, 'index'])->middleware('check.user.not.blocked')->name('history');
    Route::post('/history/get-sms', [HistoryController::class, 'getSmsCode'])->middleware('check.user.not.blocked')->name('history.get-sms');
    Route::post('/history/cancel-activation', [HistoryController::class, 'cancelActivation'])->middleware('check.user.not.blocked')->name('history.cancel-activation');
    Route::post('/history/get-social-status', [HistoryController::class, 'getSocialStatus'])->middleware('check.user.not.blocked')->name('history.get-social-status');

        // Expire activation if countdown hits zero and SMS not delivered
        Route::post('/history/expire-activation', [HistoryController::class, 'expireActivation'])->middleware('check.user.not.blocked')->name('history.expire-activation');

    Route::post('/purchase/prepare', [CountryController::class, 'preparePurchase'])->middleware('check.user.not.blocked')->name('prepare.purchase');
    Route::get('/purchase/confirm', [CountryController::class, 'showConfirm'])->middleware('check.user.not.blocked')->name('confirm.payment');
    Route::post('/payments/process', [CountryController::class, 'process'])->middleware('check.user.not.blocked')->name('payments.process');
    Route::get('/esim', [EsimController::class, 'index'])->middleware('check.user.not.blocked', 'check.service.active:esim')->name('esim');
    Route::get('/countries/{code}/plan', [EsimController::class, 'plan'])->middleware('check.user.not.blocked')->name('countries.plan');
    Route::get('/checkout/{code}/{packageId}', [EsimController::class, 'checkout'])->middleware('check.user.not.blocked')->name('checkout');
    Route::post('/checkout/{country}/{package}', [EsimController::class, 'store'])->middleware('check.user.not.blocked')->name('checkout.process');
    Route::get('/data', [DataController::class, 'showData'])->middleware('check.user.not.blocked', 'check.service.active:data')->name('data');
    Route::get('/cabletv', [CableController::class, 'showCableTV'])->middleware('check.user.not.blocked', 'check.service.active:cabletv')->name('cabletv');
    Route::post('/verify-smartcard', [CableController::class, 'verifySmartcard'])->middleware('check.user.not.blocked')->name('verify.smartcard');
    Route::post('/cabletv/purchase', [CableController::class, 'purchase'])->middleware('check.user.not.blocked')->name('cabletv.purchase');
    Route::post('/wallet/create-account', [WalletController::class, 'createAccount'])->middleware('check.user.not.blocked')->name('wallets.createAccount');
    Route::get('/profile', [Controller::class, 'profile'])->middleware('check.user.not.blocked')->name('profile');

    // Update or create transaction PIN
    Route::post('/profile/pin', [Controller::class, 'updatePin'])->middleware('check.user.not.blocked')->name('updateProfile.pin');
    Route::get('/electricity', [ElectricityController::class, 'showElectricityPlans'])->middleware('check.user.not.blocked', 'check.service.active:electricity')->name('electricity');
    Route::post('/electricity/render-success', [ElectricityController::class, 'renderSuccessPartial'])->middleware('check.user.not.blocked')->name('electricity.render-success');
    Route::post('/verify-electricity-meter', [ElectricityController::class, 'verifyElectricityMeter'])->middleware('check.user.not.blocked')->name('verify.meter');
    Route::post('/electricity/packages', [ElectricityController::class, 'getPackages'])->middleware('check.user.not.blocked')->name('electricity.packages');
    Route::post('/electricity/purchase', [ElectricityController::class, 'purchase'])->middleware('check.user.not.blocked')->name('electricity.purchase');

    

    Route::get('/social-boost', [SocialBoostController::class, 'index'])->middleware('check.user.not.blocked', 'check.service.active:social-boost')->name('social-boost.index');

    // Process purchase
    Route::post('/social-boost/purchase', [SocialBoostController::class, 'purchase'])->middleware('check.user.not.blocked')->name('social-boost.purchase');

    // View user's orders
    Route::get('/social-boost/orders', [SocialBoostController::class, 'orders'])->middleware('check.user.not.blocked')->name('social-boost.orders');

    // Check order status
    Route::get('/social-boost/status/{orderId}', [SocialBoostController::class, 'checkStatus'])->middleware('check.user.not.blocked')->name('social-boost.status');

    // Services list
    Route::get('/services', [CountryController::class, 'showServices'])->middleware('check.user.not.blocked')->name('services.list');

    // ==================== PROVIDER1 (5sim) PROVIDER ROUTES ====================
    // Step 1: Show countries for a service
    Route::get('/services/{serviceCode}/provider1/countries', [CountryController::class, 'showCountries5sim'])
        ->middleware('check.user.not.blocked')->name('services.provider1.countries');

    Route::get('/services/{serviceCode}/countries-provider1', [CountryController::class, 'showCountries5sim'])->middleware('check.user.not.blocked')->name('countries.provider1');
    Route::get('/services/{serviceCode}/countries-provider1/{country}/operators', [CountryController::class, 'showOperators5sim'])->middleware('check.user.not.blocked')->name('operators.provider1');
    Route::post('/prepare-purchase-provider1', [CountryController::class, 'preparePurchase5sim'])->middleware('check.user.not.blocked')->name('prepare.purchase.provider1');

    // Step 2: Show operators for a country
    Route::get('/services/{serviceCode}/provider1/{country}/operators', [CountryController::class, 'showOperators5sim'])
        ->middleware('check.user.not.blocked')->name('services.provider1.operators');

    // Step 3: Prepare purchase (creates session)
    Route::post('/prepare-purchase/provider1', [CountryController::class, 'preparePurchase5sim'])
        ->middleware('auth', 'check.user.not.blocked')->name('prepare.purchase.provider1');

    // ==================== PROVIDER2 (SMS-BUS) PROVIDER ROUTES ====================
    // Step 1: Show countries for a service
    Route::get('/services/{serviceId}/countries-provider2', [CountryController::class, 'showCountries'])
        ->middleware('check.user.not.blocked')->name('services.provider2.countries');

    // For AJAX loading (optional)
    Route::get('/services/{serviceId}/countries-provider2/load', [CountryController::class, 'showCountries']);

    // Step 2: Get prices for a country
    Route::get('/services/{serviceId}/countries-provider2/{countryId}/prices', [CountryController::class, 'getPrices'])
        ->middleware('check.user.not.blocked')->name('countries.provider2.prices');

    // Step 3: Prepare purchase (creates session)
    Route::post('/prepare-purchase-provider2', [CountryController::class, 'preparePurchase'])
        ->middleware('auth', 'check.user.not.blocked')->name('prepare.purchase.provider2');

    // ==================== COMMON ROUTES (BOTH PROVIDERS) ====================
    // Payment confirmation page
    Route::get('/confirm-payment', [CountryController::class, 'showConfirm'])
        ->middleware('auth', 'check.user.not.blocked')->name('confirm.payment');

    // Process payment (actual purchase)
    Route::post('/process-payment', [CountryController::class, 'process'])
        ->middleware('auth', 'check.user.not.blocked')->name('process.payment');
    // When user clicks on a product, show country selection for that product
    Route::get('/products/{id}/countries', [CountryController::class, 'showCountries'])->middleware('check.user.not.blocked')->name('products.countries');
    Route::post('/jamb', [CableController::class, 'jamb'])->middleware('check.user.not.blocked')->name('jamb');



    Route::get('/social/services', [SocialController::class, 'services'])->middleware('check.user.not.blocked')->name('social.services');
    Route::post('/social/order', [SocialController::class, 'order'])->middleware('check.user.not.blocked')->name('social.order');

    Route::get('/more-services', function () {
        return view('users.moreService');
    })->middleware('check.user.not.blocked');
    Route::get('/personal-information', function () {
        return view('users.personal_info');
    })->middleware('check.user.not.blocked');
    Route::get('/pin', function () {
        return view('users.pin');
    })->middleware('check.user.not.blocked');
    Route::post('/update-pin', [Controller::class, 'updatePin'])->middleware('check.user.not.blocked')->name('updateProfile.pin');
    Route::get('/airtime', [AirtimeController::class, 'show'])->middleware('check.user.not.blocked', 'check.service.active:airtime')->name('airtime.show');
    // Add this to your routes/web.php file
    Route::post('/render-success-partial', [AirtimeController::class, 'renderSuccessPartial'])
        ->middleware('auth', 'check.user.not.blocked')->name('render.success.partial');


    Route::get('/jambepin', [JambPinController::class, 'show'])->middleware('check.user.not.blocked')->name('jamb.show');

    Route::post('/jamb/verify-profile', [JambPinController::class, 'verifyProfileId'])->middleware('check.user.not.blocked')->name('jamb.verify-profile');

    Route::post('/jamb/purchase', [JambPinController::class, 'purchase'])->middleware('check.user.not.blocked')->name('jamb.purchase');

    Route::get('/betting', [BettingController::class, 'show'])->middleware('check.user.not.blocked')->name('bettinng.show');

    Route::post('/fund-bet', [BettingController::class, 'fund_account'])->middleware('check.user.not.blocked')->name('fund_bet');

    Route::post('/betting/verify-customer', [BettingController::class, 'verifyCustomer'])->middleware('check.user.not.blocked')->name('betting.verify-customer');

    Route::post('/clubkonnect/callback', [BettingController::class, 'handleCallback'])->middleware('check.user.not.blocked')->name('clubkonnect.callback');

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
    Route::get('/', [AdminDashboard::class, 'index'])->name('admin.dashboard');
    Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
    Route::post('/users/{id}/update-password', [AdminController::class, 'updateUserPassword'])->name('admin.users.updatePassword');
    Route::post('/users/{id}/update-pin', [AdminController::class, 'updateUserPin'])->name('admin.users.updatePin');
    Route::post('/users/{user}/add-funds', [AdminController::class, 'addFunds'])->name('admin.users.add-funds');
    Route::post('/users/{user}/deduct-funds', [AdminController::class, 'deductFunds'])->name('admin.users.deduct-funds');
    
    // User blocking routes
    Route::post('/users/{id}/block', [AdminController::class, 'blockUser'])->name('admin.users.block');
    Route::post('/users/{id}/unblock', [AdminController::class, 'unblockUser'])->name('admin.users.unblock');
    Route::get('/users/{id}/block-status', [AdminController::class, 'getUserBlockStatus'])->name('admin.users.block-status');
    
    // Services Management
    Route::get('/manage-services', [AdminController::class, 'manageServices'])->name('admin.manage-services');
    Route::post('/services/{serviceId}/toggle', [AdminController::class, 'toggleService'])->name('admin.services.toggle');
    
    Route::get('/services', [AdminServiceController::class, 'index'])->name('admin.services');
    Route::get('/services/{service}/providers', [AdminServiceController::class, 'providers'])->name('admin.providers');
    Route::get('/providers/{provider}/products', [AdminServiceController::class, 'products'])->name('admin.products');
    Route::post('/products/{id}/update', [AdminServiceController::class, 'updateProduct'])->name('admin.products.update');
    Route::post('/providers/{provider}/products/save', [AdminServiceController::class, 'saveProducts'])->name('admin.products.save');
    Route::post('/providers/{provider}/products/create', [AdminServiceController::class, 'createProduct'])->name('admin.products.create');
    Route::post('/providers/{provider}/markup', [AdminServiceController::class, 'applyMarkup'])->name('admin.providers.markup');
    Route::delete('/products/{id}', [AdminServiceController::class, 'destroy'])->name('admin.products.delete');
    Route::post('/products/{id}/toggle', [AdminServiceController::class, 'toggleActive'])->name('admin.products.toggle');
    Route::get('/esim-transaction', [AdminServiceController::class, 'esimindex'])->name('admin.esim.transaction');
    Route::get('/esim/{id}', [AdminServiceController::class, 'show'])->name('admin.esim.show');
    Route::post('/esim/upload', [AdminServiceController::class, 'upload'])->name('admin.esim.upload');

    Route::prefix('esim')->group(function () {
        Route::get('/', [AdminEsimController::class, 'index'])->name('admin.esim.index');
        Route::post('/refresh-countries', [AdminEsimController::class, 'refreshCountries'])->name('admin.esim.refresh');
        Route::post('/seed-countries', [AdminEsimController::class, 'seedSampleCountries'])->name('admin.esim.seed');
        Route::post('/{id}/update-price', [AdminEsimController::class, 'updatePrice'])->name('admin.esim.updatePrice');
        Route::post('/{countryId}/packages/save', [AdminEsimController::class, 'savePackage'])->name('admin.esim.savePackage');
        Route::delete('/packages/{id}', [AdminEsimController::class, 'deletePackage'])->name('admin.esim.deletePackage');
        Route::post('/{id}/toggle-status', [AdminEsimController::class, 'toggleCountryStatus'])->name('admin.esim.toggleStatus');
        Route::post('/{id}/toggle-local-number', [AdminEsimController::class, 'toggleLocalNumber'])->name('admin.esim.toggleLocalNumber');
        Route::get('/transaction/{id}', [AdminEsimController::class, 'showTransaction'])->name('admin.esim.transaction.show');
        
        // AJAX Routes for eSIM management
        Route::patch('/{id}', [AdminEsimController::class, 'updatePrice'])->name('admin.esim.update');
        Route::get('/{id}/packages', [AdminEsimController::class, 'getPackages'])->name('admin.esim.getPackages');
        Route::post('/{id}/packages', [AdminEsimController::class, 'storePackage'])->name('admin.esim.storePackage');
        Route::patch('/package/{id}', [AdminEsimController::class, 'updatePackage'])->name('admin.esim.updatePackage');
        Route::delete('/package/{id}', [AdminEsimController::class, 'deletePackage'])->name('admin.esim.destroyPackage');
    });

    Route::prefix('social')->group(function () {
        Route::get('/services', [SocialBoostController::class, 'adminListServices'])->name('admin.social.services');
        Route::get('/refresh', [SocialBoostController::class, 'adminRefreshServices'])->name('admin.social.refresh');
        Route::post('/update-description', [SocialBoostController::class, 'adminUpdateDescription'])->name('admin.social.updateDescription');
    });

    Route::get('/list', [AdminAuthController::class, 'index'])->name('admin.list');
    Route::get('/{admin}', [AdminAuthController::class, 'showadmin'])->name('admins.show');
    Route::post('/{admin}/update', [AdminAuthController::class, 'updateadmin'])->name('admins.update');

});
Route::middleware(['admin:super_admin'])->prefix('admin')->group(function () {
    Route::post('/create', [AdminController::class, 'createAdmin'])->name('admin.create');
});

});