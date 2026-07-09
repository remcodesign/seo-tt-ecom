<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traits;

trait HasPerPage
{
    /**
     * Resolve the per_page value from the request query string.
     * Clamped between 1 and the given maximum.
     */
    protected function getPerPage(int $default = 15, int $max = 100): int
    {
        $perPage = (int) request()->integer('per_page', $default);

        return min(max($perPage, 1), $max);
    }
}
