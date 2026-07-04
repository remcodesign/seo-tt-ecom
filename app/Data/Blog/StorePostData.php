<?php

declare(strict_types=1);

namespace App\Data\Blog;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class StorePostData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $title,

        #[Nullable, StringType]
        public ?string $body = null,

        #[Nullable, Date]
        public ?string $published_on = null,
    ) {}
}
