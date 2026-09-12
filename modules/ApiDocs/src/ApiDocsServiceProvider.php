<?php

namespace Modules\ApiDocs;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ApiDocsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-docs.php',
            'api-docs'
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'api-docs');

        $this->publishes([
            __DIR__.'/../config/api-docs.php' => config_path('api-docs.php'),
        ], 'api-docs-config');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/api-docs'),
        ], 'api-docs-assets');

        $this->ensureAssetsPublished();
    }

    private function ensureAssetsPublished(): void
    {
        $source = __DIR__.'/../public';
        $target = public_path('vendor/api-docs');

        if (! File::isDirectory($source)) {
            return;
        }

        if (! File::exists($target.'/css/api-docs.css')
            || filemtime($source.'/css/api-docs.css') > filemtime($target.'/css/api-docs.css')) {
            File::ensureDirectoryExists($target.'/css');
            File::ensureDirectoryExists($target.'/js');
            File::copyDirectory($source, $target);
        }
    }
}
