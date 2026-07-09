<?php

declare(strict_types=1);

namespace App\Data\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PaginationLinkData extends Data
{
    public function __construct(
        public ?string $url,
        public string $label,
        public ?int $page,
        public bool $active,
    ) {}
}
