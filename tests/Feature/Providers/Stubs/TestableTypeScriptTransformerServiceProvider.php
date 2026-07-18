<?php

declare(strict_types=1);

namespace Tests\Feature\Providers\Stubs;

use App\Providers\TypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;

class TestableTypeScriptTransformerServiceProvider extends TypeScriptTransformerServiceProvider
{
    public function configurePublic(TypeScriptTransformerConfigFactory $typeScriptTransformerConfigFactory): void
    {
        $this->configure($typeScriptTransformerConfigFactory);
    }
}
