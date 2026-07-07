<?php

use App\Actions\GenerateTypeScriptTypesDispatcher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'typescript:generate-types {--declarationPath=} {--outputPath=}',
    function (GenerateTypeScriptTypesDispatcher $generateTypeScriptTypesDispatcher): void {
        $generateTypeScriptTypesDispatcher->dispatchFromCommand($this);
    }
)->purpose('Generate module wrapper types from generated.d.ts');
