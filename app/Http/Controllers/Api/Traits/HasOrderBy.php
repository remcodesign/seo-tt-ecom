<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traits;

trait HasOrderBy
{
    /**
     * Define the columns that are allowed for ordering in this controller.
     * Override in the consuming controller.
     *
     * @return string[]
     */
    protected function allowedOrderByFields(): array
    {
        return [];
    }

    /**
     * Resolve the order-by column and direction from the request query string.
     * Supports a `_desc` suffix for descending order.
     *
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    protected function getOrderBy(string $default = 'created_at', string $defaultDirection = 'desc'): array
    {
        $orderBy = (string) request()->string('orderby', $default);

        // Determine direction from the _desc suffix
        $direction = 'asc';
        if (str_ends_with($orderBy, '_desc')) {
            $direction = 'desc';
            $orderBy = mb_substr($orderBy, 0, -5);
        }

        // Validate against allowed columns
        if (! in_array($orderBy, $this->allowedOrderByFields(), true)) {
            $orderBy = $default;
            $direction = $defaultDirection;
        }

        /** @var 'asc'|'desc' $direction */
        return [$orderBy, $direction];
    }
}
