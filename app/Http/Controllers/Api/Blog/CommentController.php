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

    /**
     * @return PaginatedDataCollection<int, CommentData>
     */
    public function index(): PaginatedDataCollection
    {
        $postId = request()->query('post_id');

        return CommentData::collect(
            $this->commentService->query(
                postId: $postId !== null ? (int) $postId : null,
                perPage: 15,
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
        /** @var User $user */
        $user = Auth::user();

        $post = Post::findOrFail($storeCommentData->post_id);
        $comment = $this->commentService->create($user, $post, $storeCommentData);
        $comment->load(['post', 'user']); // todo optionaly load the post and user relations, but that would require a new CommentWithPostAndUserData class that doesn't include the post and user relations to avoid circular references

        return CommentData::from($comment);
    }

    public function update(UpdateCommentData $updateCommentData, Comment $comment): CommentData
    {
        /** @var User $user */
        $user = Auth::user();

        $comment = $this->commentService->update($user, $comment, $updateCommentData);
        $comment->loadMissing(['post', 'user']); // todo optionaly load the post and user relations, but that would require a new CommentWithPostAndUserData class that doesn't include the post and user relations to avoid circular references

        return CommentData::from($comment);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->commentService->delete($user, $comment);

        return response()->json(null, 204);
    }
}
