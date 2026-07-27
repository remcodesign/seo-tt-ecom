<?php

declare(strict_types=1);

namespace App\Data\Poly\Responses;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CategoryDataResponse extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,

        public ?CarbonImmutable $created_at = null,
        public ?CarbonImmutable $updated_at = null,
    ) {}
}
