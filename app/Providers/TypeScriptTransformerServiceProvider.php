<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->replaceType(
                Carbon::class,
                'string'
            )
            ->replaceType(
                CarbonImmutable::class,
                'string'
            )
            ->transformDirectories(app_path())
            ->outputDirectory(base_path('resources/js/generated'))
            ->writer(new GlobalNamespaceWriter('generated.d.ts'))
            ->formatter(PrettierFormatter::class);
    }
}
