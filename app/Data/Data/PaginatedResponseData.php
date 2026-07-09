<?php

declare(strict_types=1);

namespace App\Data\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @template T
 */
#[TypeScript]
final class PaginatedResponseData extends Data
{
    /**
     * @param  T[]  $data
     * @param  PaginationLinkData[]  $links
     */
    public function __construct(
        public array $data,
        public array $links,
        public PaginationMetaData $meta,
    ) {}
}
