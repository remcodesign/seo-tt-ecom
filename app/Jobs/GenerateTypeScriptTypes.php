<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class GenerateTypeScriptTypes implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ?string $declarationPath = null,
        public ?string $outputPath = null,
    ) {}

    public function handle(): void
    {
        $declarationPath = $this->declarationPath ?? base_path('resources/js/generated/generated.d.ts');
        $outputPath = $this->outputPath ?? base_path('resources/js/types.ts');

        $content = $this->readDeclarationFile($declarationPath);

        $exports = $this->buildExports($content);

        $this->writeTypesFile($outputPath, $exports);
    }

    protected function readDeclarationFile(string $path): string
    {
        if (! file_exists($path)) {
            throw new RuntimeException(sprintf('Declaration file does not exist: %s', $path));
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to read declaration file: %s', $path));
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    protected function buildExports(string $content): array
    {
        $exports = [];
        $stack = [];
        $lines = preg_split('/\R/', $content) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (str_starts_with($trimmed, '//')) {
                continue;
            }

            if (preg_match('/^namespace\s+(\w+)\s*\{$/', $trimmed, $matches)) {
                $stack[] = $matches[1];

                continue;
            }

            if ($trimmed === '}') {
                array_pop($stack);

                continue;
            }

            if (preg_match('/^export\s+type\s+(\w+)\s*=/', $trimmed, $matches)) {
                $qualified = implode('.', [...$stack, $matches[1]]);

                if ($qualified !== $matches[1] && ! str_starts_with($qualified, 'App.')) {
                    $qualified = 'App.'.$qualified;
                }

                $exports[$matches[1]] = $qualified;
            }
        }

        return $exports;
    }

    /**
     * @param  array<string, string>  $exports
     */
    protected function writeTypesFile(string $path, array $exports): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $directory));
        }

        $content = <<<'TS'
// This file is generated from resources/js/generated/generated.d.ts.
// Run `php artisan typescript:generate-types` or `composer typescript`
// to refresh.

TS;

        foreach ($exports as $type => $qualified) {
            $content .= sprintf("export type %s = %s;\n", $type, $qualified);
        }

        file_put_contents($path, $content);
    }
}
