<?php

declare(strict_types=1);

use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter;
use Tests\Feature\Providers\Stubs\TestableTypeScriptTransformerServiceProvider;

// only here for the 100% coverage, since the service provider is registered in config/app.php
describe('TypeScriptTransformerServiceProvider', function (): void {
    it('registers the expected transformer configuration', function (): void {
        $typeScriptTransformerConfigFactory = app(TypeScriptTransformerConfigFactory::class);

        expect($typeScriptTransformerConfigFactory)->toBeInstanceOf(TypeScriptTransformerConfigFactory::class);

        $serviceProvider = new TestableTypeScriptTransformerServiceProvider(app());
        $serviceProvider->configurePublic($typeScriptTransformerConfigFactory);

        $outputDirectory = storage_path('app/typescript-transformer-test');
        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        $typeScriptTransformerConfig = $typeScriptTransformerConfigFactory->outputDirectory($outputDirectory)->get();

        expect($typeScriptTransformerConfig->typesWriter::class)->toBe(FlatModuleWriter::class)
            ->and($typeScriptTransformerConfig->formatter::class)->toBe(PrettierFormatter::class);
    });
});
