<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class CommentData extends Data
{
    public function __construct(
        public int $id,
        public int $post_id,
        public int $user_id,
        public string $comment,
        public PostData $post,
        public UserData $user,
    ) {}
}
