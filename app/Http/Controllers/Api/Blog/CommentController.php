<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\UpdateCommentData;
use App\Data\Blog\Responses\CommentData;
use App\Http\Controllers\Api\Traits\HasOptionalIncludes;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\PaginatedDataCollection;

readonly class CommentController
{
    use HasOptionalIncludes;

    public function __construct(private CommentService $commentService) {}

    /**
     * @return string[]
     */
    protected function allowedIncludes(): array
    {
        return ['post', 'post.user', 'user'];
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

        $includes = $this->requestIncludedRelations();

        $comment = $this->loadIncludes($comment, $includes);

        $commentData = CommentData::from($comment);
        $this->applyIncludes($commentData, $includes);

        return $commentData;
    }

    public function update(UpdateCommentData $updateCommentData, Comment $comment): CommentData
    {
        /** @var User $user */
        $user = Auth::user();

        $comment = $this->commentService->update($user, $comment, $updateCommentData);

        $includes = $this->requestIncludedRelations();

        $comment = $this->loadIncludes($comment, $includes);

        $commentData = CommentData::from($comment);
        $this->applyIncludes($commentData, $includes);

        return $commentData;
    }

    public function destroy(Comment $comment): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->commentService->delete($user, $comment);

        return response()->json(null, 204);
    }
}
