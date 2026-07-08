<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserData;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
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

        public UserData $user, // relation
        public ?PostForCommentDataResponse $post = null, // relation

        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $created_at = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $updated_at = null,
    ) {}
}
