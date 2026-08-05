<?php

namespace App\Providers;

use App\Service\TxnRefNoGenerator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TxnRefNoGenerator::class);

        $apiDocsAutoload = base_path('modules/ApiDocs/autoload.php');
        if (is_file($apiDocsAutoload)) {
            require_once $apiDocsAutoload;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('routeis', function ($expression) {
            return fnmatch($expression, Route::currentRouteName());
        });
        Schema::defaultStringLength(191);
        require_once app_path('Helpers/helpers.php');
    }
}
