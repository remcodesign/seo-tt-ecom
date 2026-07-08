<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserDataResponse;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CommentDataResponse extends Data
{
    public function __construct(
        public int $id,
        public int $post_id,
        public int $user_id,
        public string $comment,

        public UserDataResponse $user, // relation
        public ?PostForCommentDataResponse $post = null, // relation

        public ?CarbonImmutable $created_at = null,
        public ?CarbonImmutable $updated_at = null,
    ) {}
}
