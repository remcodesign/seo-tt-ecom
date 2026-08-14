<?php

declare(strict_types=1);

namespace App\Data\OpenRouter\Responses;

use Spatie\LaravelData\Data;

final class OpenRouterDataResponse extends Data
{
    /**
     * @param  array<string, mixed>  $usage
     * @param  array<string, mixed>|null  $structured
     */
    public function __construct(
        public string $text,
        public string $model,
        public array $usage,
        public ?array $structured,
    ) {}
}
