<?php

use App\Http\Controllers\Admin\Authorization\RoleController;
use App\Http\Controllers\Admin\Authorization\TeamController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\SearchingController;
use App\Http\Controllers\Admin\ArchiveController;
use App\Http\Controllers\Admin\ArchivePayoutController;
use App\Http\Controllers\Admin\BackupTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/testing', [DashboardController::class, 'testing'])->name('testing');
    Route::get('/profile/form', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [DashboardController::class, 'profileSave'])->name('profile.save');
    Route::get('/account/settings', [DashboardController::class, 'accountSetting'])->name('account.settings');
    Route::post('/security', [DashboardController::class, 'securityUpdate'])->name('security.update');

    Route::resources([
        'roles'     => RoleController::class,
        'teams'     => TeamController::class,
    ]);
    Route::get('permission/{permission}',[RoleController::class , 'addPermission']);

    Route::get('roles/delete/{id?}', [RoleController::class, 'delete'])->name('roles.delete');
    Route::get('teams/remove/{id?}', [TeamController::class, 'remove'])->name('teams.remove');
    Route::post('teams/active', [TeamController::class, 'active'])->name('teams.active');
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        return 'Cache cleared!';
    });
    Route::as('client.')->prefix('client')->group(function () {
        Route::get('/list', [ClientController::class,'list'])->name('list');
        Route::post('/store', [ClientController::class,'store'])->name('store');
        Route::get('/modal/{id?}', [ClientController::class,'modal'])->name('modal');
        Route::any('/destroy/{id}', [ClientController::class,'destroy'])->name('destroy');
        Route::get('/user/list', [ClientController::class,'userList'])->name('user_list');
        Route::post('/user/store', [ClientController::class,'userStore'])->name('user.store');
        Route::get('/sec-modal/{id?}', [ClientController::class,'modalSec'])->name('modal.sec');
    });
    
    Route::as('transaction.')->prefix('transaction')->group(function () {
        Route::get('/list', [TransactionController::class,'list'])->name('list');
        Route::get('easy-recipt/{id?}', [TransactionController::class,'easyReceipt'])->name('easy_receipt');
        Route::get('jazz-recipt/{id?}', [TransactionController::class,'jazzReceipt'])->name('jazz_receipt');
        Route::post('change-status', [TransactionController::class,'changeStatus'])->name('change_status');
        Route::post('change-status-reverse', [TransactionController::class,'changeStatusReverse'])->name('change_status_reverse');
    });
    
    Route::as('payout.')->prefix('payout')->group(function () {
        Route::get('/list', [PayoutController::class,'list'])->name('list');
        Route::get('detail/{id?}', [PayoutController::class,'detail'])->name('detail');
        Route::get('easy-recipt/{id?}', [PayoutController::class,'easyReceipt'])->name('easy_receipt');
        Route::get('jazz-recipt/{id?}', [PayoutController::class,'jazzReceipt'])->name('jazz_receipt');
    });
    Route::as('searching.')->prefix('searching')->group(function () {
        Route::get('/list', [SearchingController::class,'list'])->name('list');
        Route::get('/payout/list', [SearchingController::class,'payoutList'])->name('payout_list');
        Route::get('/sr/calculator', [SearchingController::class,'srList'])->name('sr_list');
        Route::get('/callback/{id?}', [SearchingController::class,'callback'])->name('callback.send');
    });
    Route::as('setting.')->prefix('setting')->group(function () {
        Route::get('/reverse/list', [SettingController::class,'addSetting'])->name('list');
        Route::any('/reverse/ok_list', [SettingController::class,'okList'])->name('ok_list');
        Route::get('/modal/{id?}', [SettingController::class,'modal'])->name('modal');
        Route::get('/third_modal/{id?}', [SettingController::class,'modalThird'])->name('third_modal');
        Route::get('/surplus-modal', [SettingController::class,'modalSec'])->name('modal_sec');
        Route::post('/save', [SettingController::class,'saveSetting'])->name('save');
        Route::post('/surplus-save', [SettingController::class,'saveSurplus'])->name('save_surplus');
        Route::post('/assigned-amount-save', [SettingController::class,'saveAssignedAmount'])->name('save_assigned_amount');
        Route::post('/schedule/save', [SettingController::class,'saveScheduleSetting'])->name('schedule.save');
        Route::get('/get/suspend-setting', [SettingController::class,'getSuspendSetting'])->name('get.suspend');
        Route::post('/api-suspend/save', [SettingController::class,'apiSuspendSetting'])->name('api.suspend');
        Route::post('/manual_payout/save', [ManualPayoutController::class,'save'])->name('manual_payout.save');
        Route::get('/manual_payout/detail/{id?}', [ManualPayoutController::class,'detail'])->name('detail');
        Route::get('easy-recipt/{id?}', [ManualPayoutController::class,'easyReceipt'])->name('easy_receipt');
        Route::get('jazz-recipt/{id?}', [ManualPayoutController::class,'jazzReceipt'])->name('jazz_receipt');
    });
    Route::as('settlement.')->prefix('settlement')->group(function () {
        Route::get('/ok/list', [SettlementController::class,'okList'])->name('ok');
        Route::get('/piq/list', [SettlementController::class,'piqList'])->name('piq');
        Route::get('/pkn/list', [SettlementController::class,'pknList'])->name('pkn');
        Route::get('/cspkr/list', [SettlementController::class,'cspkrList'])->name('cspkr');
        Route::get('/toppay/list', [SettlementController::class,'toppayList'])->name('toppay');
        Route::get('/corepay/list', [SettlementController::class,'corepayList'])->name('corepay');
        Route::get('/genxpay/list', [SettlementController::class,'genxpayList'])->name('genxpay');
        Route::get('/moneypay/list', [SettlementController::class,'moneypayList'])->name('moneypay');
        Route::post('/store', [SettlementController::class,'store'])->name('store');
        Route::get('/modal/{id?}', [SettlementController::class,'modal'])->name('modal');
    });
    Route::get('/check-status/{id?}/{type?}',[TransactionController::class, 'statusInquiry'])->name('jazzcash.status-inquiry');
    Route::get('/archive/list', [ArchiveController::class, 'list'])->name('archive.list');
    Route::get('/archive/payout/list', [ArchivePayoutController::class, 'list'])->name('archive.payout_list');
    Route::get('/backup/transaction/list', [BackupTransactionController::class, 'list'])->name('archive.backup_list');
});

