<?php

declare(strict_types=1);

namespace App\Services\Poly;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class CategoryService
{
    /**
     * Query categories that have a categorizable relation of the given type,
     * with pagination and order-by support.
     *
     * @param  'asc'|'desc'  $orderByDirection
     * @return LengthAwarePaginator<int, Category>
     */
    public function query(
        string $categorizableType,
        int $perPage = 20,
        string $orderByColumn = 'name',
        string $orderByDirection = 'asc',
    ): LengthAwarePaginator {
        return Category::query()
            ->whereIn('id', function ($query) use ($categorizableType): void {
                $query->select('category_id')
                    ->from('categorizables')
                    ->where('categorizable_type', $categorizableType);
            })
            ->orderBy($orderByColumn, $orderByDirection)
            ->paginate($perPage);
    }
}
