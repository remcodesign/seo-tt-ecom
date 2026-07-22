<?php

declare(strict_types=1);

namespace App\Data\Blog\Requests;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class StorePostData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('users', 'id')]
        public int $user_id,

        #[Required, StringType, Max(255)]
        public string $title,

        /** @var array<int> */
        #[Sometimes]
        public array $category_ids = [],

        #[Sometimes, Nullable, StringType]
        public ?string $body = null,

        #[Sometimes, Nullable, Date, WithCast(DateTimeInterfaceCast::class, ['Y-m-d', 'Y-m-d H:i:s'])]
        public ?CarbonImmutable $published_on = null,
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
