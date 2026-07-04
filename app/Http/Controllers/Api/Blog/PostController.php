<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Data\Blog\PostData;
use App\Data\Blog\StorePostData;
use App\Data\Blog\UpdatePostData;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\PaginatedDataCollection;

class PostController
{
    public function __construct(private readonly PostService $postService) {}

    /**
     * @return PaginatedDataCollection<int, PostData>
     */
    public function index(): PaginatedDataCollection
    {
        $lengthAwarePaginator = $this->postService->query(perPage: 15);

        return PostData::collect($lengthAwarePaginator, PaginatedDataCollection::class);
    }

    public function show(Post $post): PostData
    {
        $post = $this->postService->find($post, withComments: true);

        return PostData::from($post);
    }

    public function store(StorePostData $storePostData): PostData
    {
        /** @var User $user */
        $user = Auth::user();

        $post = $this->postService->create($user, $storePostData->toArray());

        return PostData::from($post);
    }

    public function update(UpdatePostData $updatePostData, Post $post): PostData
    {
        /** @var User $user */
        $user = Auth::user();

        $post = $this->postService->update($user, $post, $updatePostData->toArray());

        return PostData::from($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->postService->delete($user, $post);

        return response()->json(null, 204);
    }
}
