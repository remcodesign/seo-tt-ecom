<?php

declare(strict_types=1);

use App\Jobs\GenerateTypeScriptTypes;

it('converts generated.d.ts namespace output into module wrapper exports', function (): void {
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
        namespace Blog {
            namespace Responses {
                export type PostData = {
                    id: number;
                    title: string;
                    user: App.Data.Auth.CreateTokenData | null;
                };
            }
        }
    }
}
TS;

    $tempDirectory = sys_get_temp_dir().'/'.uniqid('tsgen-', true);
    mkdir($tempDirectory, 0755, true);

    $declarationPath = $tempDirectory.'/generated.d.ts';
    $outputPath = $tempDirectory.'/types.ts';

    file_put_contents($declarationPath, $declaration);

    $job = new GenerateTypeScriptTypes($declarationPath, $outputPath);
    $job->handle();

    expect(file_exists($outputPath))->toBeTrue();

    $content = file_get_contents($outputPath);

    expect($content)->toContain('export type CreateTokenData = App.Data.Auth.CreateTokenData;')
        ->and($content)->toContain('export type PostData = App.Data.Blog.Responses.PostData;');
});
