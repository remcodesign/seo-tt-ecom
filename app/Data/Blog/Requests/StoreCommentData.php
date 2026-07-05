<?php

declare(strict_types=1);

namespace App\Data\Blog\Requests;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class StoreCommentData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('blog_posts', 'id')]
        public int $post_id,

        #[Required, StringType, Max(65535)]
        public string $comment,
    ) {}
}
