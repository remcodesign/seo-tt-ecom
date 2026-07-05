<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserData;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Lightweight Post DTO for use in comment contexts.
 * Excludes content-heavy fields (body, future images, etc.)
 * to keep responses minimal and DB-efficient.
 */
#[TypeScript]
final class PostForCommentData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $title,
        public string $slug,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $published_on,
        public ?UserData $user = null,
    ) {}
}
