<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserData;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Lightweight Post DTO for use in comment contexts.
 * Excludes content-heavy fields (body, future images, etc.)
 * to keep responses minimal and DB-efficient.
 */
#[TypeScript]
final class PostForCommentDataResponse extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $title,
        public string $slug,

        public ?UserData $user = null, // relation

        public ?CarbonImmutable $published_on = null,
        public ?CarbonImmutable $created_at = null,
        public ?CarbonImmutable $updated_at = null,
    ) {}
}
