<?php

use Illuminate\Support\Facades\Route;
use Modules\ApiDocs\Http\Controllers\DocsController;

Route::redirect('api-docs', 'api-docs/get-started')->name('api-docs.index');

Route::prefix('api-docs')->name('api-docs.')->group(function () {
    Route::get('/{page}', [DocsController::class, 'show'])
        ->name('show')
        ->where('page', '[a-z0-9\-]+');
});
