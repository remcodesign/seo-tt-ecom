<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\GenerateTypeScriptTypes;
use Illuminate\Console\Command;

final class GenerateTypeScriptTypesDispatcher
{
    public function dispatchFromCommand(Command $command): void
    {
        $declarationPath = $command->option('declarationPath');
        $outputPath = $command->option('outputPath');

        if (! is_string($declarationPath)) {
            $declarationPath = null;
        }

        if (! is_string($outputPath)) {
            $outputPath = null;
        }

        /** @var string|null $declarationPath */
        /** @var string|null $outputPath */
        GenerateTypeScriptTypes::dispatchSync(
            $declarationPath,
            $outputPath,
        );
    }
}
