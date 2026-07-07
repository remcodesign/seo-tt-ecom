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
        return ['user', 'comments'];
    }

    /**
     * @return PaginatedDataCollection<int, PostData>
     */
    public function index(): PaginatedDataCollection
    {
        // todo use the optional includes for the index and show methods, not for store and update
        // ?maybe also remove index and show methods from the PostService, and just use the query method for both index and show, with the optional includes applied
        return PostData::collect(
            $this->postService->query(
                withComments: false,
                perPage: 10
            ),
            PaginatedDataCollection::class
        );
    }

    public function show(Post $post): PostData
    {
        // todo use the optional includes for the index and show methods, not for store and update
        // ?maybe also remove index and show methods from the PostService, and just use the query method for both index and show, with the optional includes applied

        if ($post->published_on === null) {
            abort(404, 'Post not found.');
        }

        $post = $this->postService->find($post, withComments: true);

        return PostData::from($post);
    }

    public function store(StorePostData $storePostData): PostData
    {
        $post = $this->postService->create($this->user(), $storePostData);

        // todo remove and use optional includes only for the index and show methods, not for store and update
        [$post, $includes] = $this->resolveOptionalIncludes($post);
        $postData = PostData::from($post);
        $this->applyIncludes($postData, $includes);

        // todo use $postDataModfied DTO to return the modified data with the includes applied, instead of returning the original $postData
        return $postData;
    }

    public function update(UpdatePostData $updatePostData, Post $post): PostData
    {
        $post = $this->postService->update($this->user(), $post, $updatePostData);

        // todo remove and use optional includes only for the index and show methods, not for store and update
        [$post, $includes] = $this->resolveOptionalIncludes($post);
        $postData = PostData::from($post);
        $this->applyIncludes($postData, $includes);

        // todo use $postDataModfied DTO to return the modified data with the includes applied, instead of returning the original $postData
        return $postData;
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->delete($this->user(), $post);

        return response()->json(null, 204);
    }
}
