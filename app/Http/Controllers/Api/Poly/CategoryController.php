<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Poly;

use App\Data\Poly\Responses\CategoryDataResponse;
use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\Api\Traits\HasOrderBy;
use App\Http\Controllers\Api\Traits\HasPerPage;
use App\Models\Blog\Post;
use App\Services\Poly\CategoryService;
use Spatie\LaravelData\PaginatedDataCollection;

class CategoryController extends Controller
{
    use HasOrderBy;
    use HasPerPage;

    public function __construct(private CategoryService $categoryService) {}

    /**
     * Define the columns that are allowed for ordering in this controller.
     *
     * @return string[]
     */
    protected function allowedOrderByFields(): array
    {
        return ['name', 'created_at', 'updated_at'];
    }

    /**
     * @return PaginatedDataCollection<int, CategoryDataResponse>
     */
    public function index(): PaginatedDataCollection
    {
        $categorizableType = match (request()->query('type')) {
            'blog_post' => Post::class,
            default     => abort(422, 'The type parameter is required and must be a valid categorizable type.'),
        };

        [$orderByColumn, $orderByDirection] = $this->getOrderBy('name', 'asc');

        return CategoryDataResponse::collect(
            $this->categoryService->query(
                categorizableType: $categorizableType,
                perPage: $this->getPerPage(default: 50, max: 100),
                orderByColumn: $orderByColumn,
                orderByDirection: $orderByDirection,
            ),
            PaginatedDataCollection::class,
        );
    }
}
