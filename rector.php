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

    // 1. Upgrade automatically to the current PHP version (PHP 8.4)
    ->withPhpSets()

    // 2. Match Laravel specific refactoring and Composer-based rules
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)

    // 3. All refactoring rules are enabled
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true
    );
