<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/contact-us', 'contactus')->name('contactus');
    Route::get('/about-us', 'aboutus')->name('aboutus');
    Route::get('/faqs', 'faqs')->name('faqs');
    Route::get('/services', 'services')->name('services');
    Route::get('/products', 'products')->name('products');
    Route::get('/products-detail/{id?}', 'productDetail')->name('product.detail');
    Route::get('/policy', 'policy')->name('policy');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/refund', 'refund')->name('refund');
    Route::post('/checkout', 'checkout')->name('checkout');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
