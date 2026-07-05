<?php

declare(strict_types=1);

namespace App\Data\Blog\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdateCommentData extends Data
{
    public function __construct(
        #[Sometimes, StringType, Max(65535)]
        public ?string $comment = null,
    ) {}
}
