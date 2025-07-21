<?php

use App\Http\Controllers\Api\TestPayinController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PayinController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\JazzCashCallbackController;
use App\Http\Controllers\Api\PaymentCheckoutController;
use App\Http\Controllers\Api\PayoutCheckoutController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Route::as('payin.')->prefix('payin')->group(function () {
//    Route::post('/checkout',[PayinController::class, 'checkout']);
//});

Route::as('payin.')->prefix('payin')->group(function () {
    Route::post('/checkout',[PayinController::class, 'checkout'])
        ->middleware('log.rejected');
});




Route::post('v1/payin-checkout',[PaymentCheckoutController::class, 'checkoutProceed'])
    ->middleware(['payment.validate', 'check.blocked.numbers']);


Route::as('payout.')->prefix('payout')->group(function () {
    // Route without whitelist.ip middleware
    Route::post('/checkout', [PayoutController::class, 'checkout']);
});

/*Route::as('payout.')->prefix('payout')->group(function () {
//    Route::middleware('whitelist.ip')->group(function () {
//        Route::post('/checkout',[PayoutController::class, 'checkout']);
//    });
//});
*/
Route::post('/payin-status-check', [GeneralController::class , 'checkStatus']);
Route::post('/payout-status-check', [GeneralController::class , 'checkPayoutStatus']);
Route::get('/get-dashboard-data', [GeneralController::class , 'dashboardData']);


/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|    
*/
Route::get('v1/get-dashboard-data', [GeneralController::class , 'dashboardDataV1'])->middleware('auth.api.key');
Route::prefix('v1')->middleware(['hmac.authenticate'])->group(function () {
    //Route::post('payment-checkout', [TestPayinController::class, 'checkout']);// testing purpose only
    // payin route
    Route::post('payment-checkout', [PayinController::class, 'checkout']);

    // Payout Route
    Route::post('payout/checkout', [PayoutController::class, 'checkout'])
        ->middleware('whitelist.ip');
});

Route::post('/jazzcash/callback', [JazzCashCallbackController::class, 'handleCallback']);
/*
|--------------------------------------------------------------------------
| API teting Routes
|--------------------------------------------------------------------------
|    
*/
Route::post('/payout/demo-checkout', [PayoutCheckoutController::class, 'payoutProceed']);
