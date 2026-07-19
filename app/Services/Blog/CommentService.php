<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\UpdateCommentData;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

readonly class CommentService
{
    public function create(User $user, Post $post, StoreCommentData $storeCommentData): Comment
    {
        return $post->comments()->create([
            'user_id' => $user->id,
            'comment' => $storeCommentData->comment,
        ]);
    }

    public function update(User $user, Comment $comment, UpdateCommentData $updateCommentData): Comment
    {
        // todo remove `$user` parameter once we have a DTO validation rule that ensures the user is authorized to update the comment.

        // Filter out null values to avoid overwriting existing fields with null
        $data = collect([
            'comment' => $updateCommentData->comment,
        ])->filter(static fn (mixed $value): bool => $value !== null)->all();

        // todo covert to `$data = $updateCommentData->toArray();`
        // .. and directly pass to `$comment->update($data)` once we have a DTO validation rule that ensures at least one field is present for update.

        if ($data !== []) {
            $comment->update($data);
        }

        return $comment;
    }

    /**
     * Delete the given comment.
     */
    public function delete(Comment $comment): ?bool
    {
        return $comment->delete();
    }

    /**
     * Query comments with pagination, optional post filter, and order-by support,
     * with eager-loaded relations to prevent N+1.
     *
     * @param  'asc'|'desc'  $orderByDirection
     * @return LengthAwarePaginator<int, Comment>
     */
    public function query(
        ?int $postId = null,
        int $perPage = 15,
        string $orderByColumn = 'created_at',
        string $orderByDirection = 'desc',
    ): LengthAwarePaginator {
        $builder = Comment::query()
            // Only include comments for published posts, and eager-load the post relation
            ->whereHas('post', function ($query): void {
                /** @var Builder<Post> $query */
                $query->published();
            })
            // Eager-load the post relation, but exclude content-heavy fields (body, etc.)
            ->with([
                'post' => function ($query): void {
                    /** @var Builder<Post> $query */
                    $query->withoutContentFields();
                },
                'post.user',
                'user',
            ]);

        if ($postId !== null) {
            $builder->where('post_id', $postId);
        }

        return $builder->orderBy($orderByColumn, $orderByDirection)->paginate($perPage);
    }

    public function find(Comment $comment): Comment
    {
        return $comment->load(['post' => fn ($query) => $query->withoutContentFields()])
            ->load(['post.user', 'user']);
    }
}
