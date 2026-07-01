<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Blog\StorePostRequest;
use App\Http\Requests\Api\Blog\UpdatePostRequest;
use App\Models\Post;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PostController extends Controller
{
    public function __construct(private readonly PostService $service) {}

    public function index(): ResourceCollection
    {
        $posts = $this->service->query(perPage: 15);

        return JsonResource::collection($posts);
    }

    public function show(Post $post): JsonResource
    {
        $post = $this->service->find($post, withComments: true);

        return JsonResource::make($post);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $post = $this->service->create($user, $request->validated());

        return response()->json($post, 201);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $post = $this->service->update($user, $post, $request->validated());

        return response()->json($post);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->service->delete($user, $post);

        return response()->json(null, 204);
    }
}
