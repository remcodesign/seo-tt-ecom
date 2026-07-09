<?php

declare(strict_types=1);

namespace App\Data\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PaginationMetaData extends Data
{
    public function __construct(
        public int $current_page,
        public string $first_page_url,
        public ?int $from,
        public int $last_page,
        public string $last_page_url,
        public ?string $next_page_url,
        public string $path,
        public int $per_page,
        public ?string $prev_page_url,
        public ?int $to,
        public int $total,
    ) {}
}
