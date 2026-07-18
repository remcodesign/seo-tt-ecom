<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use Spatie\LaravelData\Data;

class HasOptionalIncludesDataStub extends Data
{
    public array $included = [];

    public function include(string ...$includes): static
    {
        $this->included = $includes;

        return $this;
    }
}
