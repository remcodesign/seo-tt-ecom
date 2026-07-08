<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PostDataModifiedResponse extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $title,
        public string $slug,
        public ?string $body = null,

        public ?CarbonImmutable $published_on = null,
        public ?CarbonImmutable $created_at = null,
        public ?CarbonImmutable $updated_at = null,
    ) {}
}
