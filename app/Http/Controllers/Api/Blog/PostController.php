<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Data\Blog\Requests\StorePostData;
use App\Data\Blog\Requests\UpdatePostData;
use App\Data\Blog\Responses\PostData;
use App\Http\Controllers\Api\Traits\HasOptionalIncludes;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\PaginatedDataCollection;

readonly class PostController
{
    use HasOptionalIncludes;

    public function __construct(private PostService $postService) {}

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }

    /**
     * @return string[]
     */
    protected function allowedIncludes(): array
    {
        return ['user'];
    }

    /**
     * @return PaginatedDataCollection<int, PostData>
     */
    public function index(): PaginatedDataCollection
    {
        $lengthAwarePaginator = $this->postService->query(withComments: false, perPage: 15);

        return PostData::collect($lengthAwarePaginator, PaginatedDataCollection::class);
    }

    public function show(Post $post): PostData
    {
        $post = $this->postService->find($post, withComments: true);

        return PostData::from($post);
    }

    public function store(StorePostData $storePostData): PostData
    {
        $post = $this->postService->create($this->user(), $storePostData);

        [$post, $includes] = $this->resolveOptionalIncludes($post);
        $postData = PostData::from($post);
        $this->applyIncludes($postData, $includes);

        return $postData;
    }

    public function update(UpdatePostData $updatePostData, Post $post): PostData
    {
        $post = $this->postService->update($this->user(), $post, $updatePostData);

        [$post, $includes] = $this->resolveOptionalIncludes($post);
        $postData = PostData::from($post);
        $this->applyIncludes($postData, $includes);

        return $postData;
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->delete($this->user(), $post);

        return response()->json(null, 204);
    }
}
