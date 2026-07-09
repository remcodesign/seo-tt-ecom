<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\UpdateCommentData;
use App\Data\Blog\Responses\CommentDataModifiedResponse;
use App\Data\Blog\Responses\CommentDataResponse;
use App\Http\Controllers\Api\Traits\HasOrderBy;
use App\Http\Controllers\Api\Traits\HasPerPage;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelData\PaginatedDataCollection;

readonly class CommentController
{
    use HasOrderBy;
    use HasPerPage;

    public function __construct(private CommentService $commentService) {}

    private function user(): User
    {
        $user = Auth::user();
        assert($user instanceof User);

        return $user;
    }

    /**
     * Define the columns that are allowed for ordering in this controller.
     *
     * @return string[]
     */
    protected function allowedOrderByFields(): array
    {
        return ['created_at', 'updated_at'];
    }

    /**
     * @return PaginatedDataCollection<int, CommentDataResponse>
     */
    public function index(): PaginatedDataCollection
    {
        $postId = request()->query('post_id');
        [$orderByColumn, $orderByDirection] = $this->getOrderBy('created_at', 'desc');

        return CommentDataResponse::collect(
            $this->commentService->query(
                postId: $postId !== null ? (int) $postId : null,
                perPage: $this->getPerPage(default: 5, max: 100),
                orderByColumn: $orderByColumn,
                orderByDirection: $orderByDirection,
            ),
            PaginatedDataCollection::class,
        );
    }

    public function show(Comment $comment): CommentDataResponse
    {
        $comment = $this->commentService->find($comment);

        if ($comment->post->published_on === null) {
            abort(404, 'Comment not found.');
        }

        return CommentDataResponse::from($comment);
    }

    public function store(StoreCommentData $storeCommentData): CommentDataModifiedResponse
    {
        $post = Post::findOrFail($storeCommentData->post_id);
        $comment = $this->commentService->create($this->user(), $post, $storeCommentData);

        return CommentDataModifiedResponse::from($comment);
    }

    public function update(UpdateCommentData $updateCommentData, Comment $comment): CommentDataModifiedResponse
    {
        $comment = $this->commentService->update($this->user(), $comment, $updateCommentData);

        return CommentDataModifiedResponse::from($comment);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->commentService->delete($this->user(), $comment);

        return response()->json(null, 204);
    }
}
