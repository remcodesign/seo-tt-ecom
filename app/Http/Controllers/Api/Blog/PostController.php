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
        // todo add comments count to the response, but that would require a new PostWithCommentsCountData class that doesn't include the comments relation to avoid circular references
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

        $post = $this->postService->create($user, $storePostData);

        $includes = $this->requestIncludedRelations();

        $post = $this->loadIncludes($post, $includes);

        $postData = PostData::from($post);
        $this->applyIncludes($postData, $includes);

        return $postData;
    }

    public function update(UpdatePostData $updatePostData, Post $post): PostData
    {
        /** @var User $user */
        $user = Auth::user();

        $post = $this->postService->update($user, $post, $updatePostData);

        $includes = $this->requestIncludedRelations();

        $post = $this->loadIncludes($post, $includes);

        $postData = PostData::from($post);
        $this->applyIncludes($postData, $includes);

        return $postData;
    }

    public function destroy(Post $post): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->postService->delete($user, $post);

        return response()->json(null, 204);
    }
}
