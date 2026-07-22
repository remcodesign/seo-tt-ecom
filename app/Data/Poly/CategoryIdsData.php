<?php

declare(strict_types=1);

namespace App\Data\Poly;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;

final class CategoryIdsData extends Data
{
    /**
     * @param  array<int>  $category_ids
     */
    public function __construct(
        /** @var array<int> */
        #[Sometimes, IntegerType]
        public array $category_ids,
    ) {}

    /**
     * @param  mixed  $context
     * @return array<string, array<int, string>>
     */
    public static function rules($context = null): array
    {
        return [
            'category_ids' => ['present', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
