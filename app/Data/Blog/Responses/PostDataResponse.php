<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserDataResponse;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PostDataResponse extends Data
{
    /**
     * @param  CommentDataResponse[]|null  $comments
     */
    public function __construct(
        public int $id,
        public int $user_id,
        public string $title,
        public string $slug,

        public UserDataResponse $user, // relation
        public ?string $body = null,
        public ?array $comments = null, // relation
        public int $comments_count = 0,

        public ?CarbonImmutable $published_on = null,
        public ?CarbonImmutable $created_at = null,
        public ?CarbonImmutable $updated_at = null,
    ) {}

}
