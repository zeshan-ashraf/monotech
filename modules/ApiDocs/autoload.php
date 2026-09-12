<?php

/**
 * PSR-4 autoloader for the ApiDocs module.
 * Loaded from AppServiceProvider so the module works without composer dump-autoload.
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'Modules\\ApiDocs\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__.'/src/'.str_replace('\\', '/', $relative).'.php';

    if (is_file($file)) {
        require_once $file;
    }
});
