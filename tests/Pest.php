<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->beforeEach(function (): void {
    $hotFile = public_path('hot');

    if (file_exists($hotFile)) {
        @unlink($hotFile);
    }
});

pest()->browser()->timeout(10_000);
