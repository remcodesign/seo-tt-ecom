<?php

declare(strict_types=1);

namespace App\Data\Poly;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CategoryIdsData extends Data
{
    /**
     * @param  array<int>  $category_ids
     */
    public function __construct(
        /** @var array<int> */
        public array $category_ids,
    ) {}
}
