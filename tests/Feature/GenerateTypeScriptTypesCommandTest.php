<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('runs the typescript:generate-types command and creates the wrapper file', function (): void {
    $declaration = <<<'TS'
declare namespace App {
    namespace Data {
        namespace Auth {
            export type CreateTokenData = {
                email: string;
                password: string;
                device_name: string;
            };
        }
    }
}
TS;

    $tempDirectory = sys_get_temp_dir().'/'.uniqid('tsgen-cmd-', true);
    mkdir($tempDirectory, 0755, true);

    $declarationPath = $tempDirectory.'/generated.d.ts';
    $outputPath = $tempDirectory.'/types.ts';

    file_put_contents($declarationPath, $declaration);

    Artisan::call('typescript:generate-types', [
        '--declarationPath' => $declarationPath,
        '--outputPath' => $outputPath,
    ]);

    expect(file_exists($outputPath))->toBeTrue();

    $content = file_get_contents($outputPath);

    expect($content)->toContain('export type CreateTokenData = App.Data.Auth.CreateTokenData;');
});
