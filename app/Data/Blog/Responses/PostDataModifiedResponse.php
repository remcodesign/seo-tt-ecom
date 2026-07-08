<?php

declare(strict_types=1);

namespace App\Data\Blog\Responses;

use App\Data\Auth\UserData;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PostDataModifiedResponse extends Data
{
    /**
     * @param  CommentDataResponse[]|null  $comments
     */
    public function __construct(
        public int $id,
        public int $user_id,
        public string $title,
        public string $slug,
        public ?string $body = null,

        public ?UserData $user = null, // relation
        #[DataCollectionOf(CommentDataResponse::class)]
        public ?array $comments = null, // relation

        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $published_on = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $created_at = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?CarbonImmutable $updated_at = null,
    ) {}
}
