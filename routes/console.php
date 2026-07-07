<?php

use App\Jobs\GenerateTypeScriptTypes;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('typescript:generate-types {--declarationPath=} {--outputPath=}', function (): void {
    GenerateTypeScriptTypes::dispatchSync(
        $this->option('declarationPath') ?: null,
        $this->option('outputPath') ?: null,
    );
})->purpose('Generate module wrapper types from generated.d.ts');
