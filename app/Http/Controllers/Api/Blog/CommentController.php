<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\UpdateCommentData;
use App\Data\Blog\Responses\CommentData;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\PaginatedDataCollection;

readonly class CommentController
{
    public function __construct(private CommentService $commentService) {}

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }

    /**
     * @return PaginatedDataCollection<int, CommentData>
     */
    public function index(): PaginatedDataCollection
    {
        $postId = request()->query('post_id');

        return CommentData::collect(
            $this->commentService->query(
                postId: $postId !== null ? (int) $postId : null,
                perPage: 5,
            ),
            PaginatedDataCollection::class,
        );
    }

    public function show(Comment $comment): CommentData
    {
        $comment = $this->commentService->find($comment);

        return CommentData::from($comment);
    }

    public function store(StoreCommentData $storeCommentData): CommentData
    {
        $post = Post::findOrFail($storeCommentData->post_id);
        $comment = $this->commentService->create($this->user(), $post, $storeCommentData);

        return CommentData::from($comment);
    }

    public function update(UpdateCommentData $updateCommentData, Comment $comment): CommentData
    {
        $comment = $this->commentService->update($this->user(), $comment, $updateCommentData);

        return CommentData::from($comment);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->commentService->delete($this->user(), $comment);

        return response()->json(null, 204);
    }
}
