<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Blog\StorePostRequest;
use App\Http\Requests\Api\Blog\UpdatePostRequest;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct(private readonly PostService $postService) {}

    public function index(): ResourceCollection
    {
        $lengthAwarePaginator = $this->postService->query(perPage: 15);

        return JsonResource::collection($lengthAwarePaginator);
    }

    public function show(Post $post): JsonResource
    {
        $post = $this->postService->find($post, withComments: true);

        return JsonResource::make($post);
    }

    public function store(StorePostRequest $storePostRequest): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $post = $this->postService->create($user, $storePostRequest->validated());

        return response()->json($post, 201);
    }

    public function update(UpdatePostRequest $updatePostRequest, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $post = $this->postService->update($user, $post, $updatePostRequest->validated());

        return response()->json($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->postService->delete($user, $post);

        return response()->json(null, 204);
    }
}
