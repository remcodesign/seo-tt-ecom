<?php

declare(strict_types=1);

namespace App\Data\Blog;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PostData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $title,
        public ?string $body,
        public string $slug,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $published_on,
    ) {}

}
