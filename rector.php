<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        // __DIR__ . '/bootstrap',
        // __DIR__ . '/config',
        // __DIR__ . '/public',
        // __DIR__ . '/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])

    // 1. Upgrade automatisch naar je huidige PHP-versie (PHP 8.3 / 8.4)
    ->withPhpSets()

    // 2. Koppel Laravel specifieke refactoring en Composer-gebaseerde regels
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)

    // 3. Draai alle stabiele kwaliteits- en moderniseringsknoppen open naar 100%
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true, // Dit dekt type coverage al volledig af!
        privatization: true,
        instanceOf: true,
        earlyReturn: true
    );
