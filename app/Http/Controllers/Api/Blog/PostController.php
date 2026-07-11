<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Data\Blog\Requests\StorePostData;
use App\Data\Blog\Requests\UpdatePostData;
use App\Data\Blog\Responses\PostDataModifiedResponse;
use App\Data\Blog\Responses\PostDataResponse;
use App\Enums\RoleLabel;
use App\Http\Controllers\Api\Traits\HasOptionalIncludes;
use App\Http\Controllers\Api\Traits\HasOrderBy;
use App\Http\Controllers\Api\Traits\HasPerPage;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\PaginatedDataCollection;

readonly class PostController
{
    use HasOptionalIncludes; // currently not used, but kept for phpstan dead code detection
    use HasOrderBy;
    use HasPerPage;

    public function __construct(private PostService $postService) {}

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }

    private function authorizePostOwnerOrAdmin(Post $post): void
    {
        $user = $this->user();

        if ($user->isNot($post->user) && $user->role_label !== RoleLabel::admin) {
            abort(403, 'You are not authorized to modify this post.');
        }
    }

    /**
     * Define the columns that are allowed for ordering in this controller.
     *
     * @return string[]
     */
    protected function allowedOrderByFields(): array
    {
        return ['published_on', 'updated_at'];
    }

    /**
     * @return PaginatedDataCollection<int, PostDataResponse>
     */
    public function index(): PaginatedDataCollection
    {
        [$orderByColumn, $orderByDirection] = $this->getOrderBy('published_on', 'desc');

        return PostDataResponse::collect(
            $this->postService->query(
                withComments: false,
                perPage: $this->getPerPage(default: 6, max: 12),
                orderByColumn: $orderByColumn,
                orderByDirection: $orderByDirection,
            ),
            PaginatedDataCollection::class
        );
    }

    public function show(Post $post): PostDataResponse
    {
        // todo use the optional includes for the index and show methods, not for store and update
        // ?maybe also remove index and show methods from the PostService, and just use the query method for both index and show, with the optional includes applied

        if ($post->published_on === null) {
            abort(404, 'Post not found.');
        }

        $post = $this->postService->find($post, withComments: true);

        return PostDataResponse::from($post);
    }

    public function store(StorePostData $storePostData): PostDataModifiedResponse
    {
        $post = $this->postService->create($this->user(), $storePostData);

        return PostDataModifiedResponse::from($post);
    }

    public function update(UpdatePostData $updatePostData, Post $post): PostDataModifiedResponse
    {
        $this->authorizePostOwnerOrAdmin($post);

        $post = $this->postService->update($this->user(), $post, $updatePostData);

        return PostDataModifiedResponse::from($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorizePostOwnerOrAdmin($post);
        $this->postService->delete($this->user(), $post);

        return response()->json(null, 204);
    }
}
