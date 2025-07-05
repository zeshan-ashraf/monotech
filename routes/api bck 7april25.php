<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PayinController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\TestPayinController;

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
Route::as('payin.')->prefix('payin')->group(function () {
    Route::post('/checkout',[PayinController::class, 'checkout']);
});

Route::post('v1/payment-checkout', [TestPayinController::class, 'checkout'])
    ->middleware(['hmac.authenticate']);

Route::as('payout.')->prefix('payout')->group(function () {
    Route::middleware('whitelist.ip')->group(function () {
        Route::post('/checkout',[PayoutController::class, 'checkout']);
    });
});

Route::post('/payin-status-check', [GeneralController::class , 'checkStatus']);
Route::post('/payout-status-check', [GeneralController::class , 'checkPayoutStatus']);
Route::get('/get-dashboard-data', [GeneralController::class , 'dashboardData']);