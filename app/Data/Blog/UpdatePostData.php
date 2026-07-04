<?php

declare(strict_types=1);

namespace App\Data\Blog;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdatePostData extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(255)]
        public ?string $title = null,

        #[Sometimes, Nullable, StringType]
        public ?string $body = null,

        #[Sometimes, Nullable, Date]
        public ?string $published_on = null,
    ) {}
}
