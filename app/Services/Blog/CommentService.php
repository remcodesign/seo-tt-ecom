<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\UpdateCommentData;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        if ($user->isNot($comment->user)) {
            throw new AuthorizationException('You are not the owner of this comment.');
        }

        $data = array_filter([
            'comment' => $updateCommentData->comment,
        ], static fn (mixed $value): bool => $value !== null);

        if ($data !== []) {
            $comment->update($data);
        }

        return $comment;
    }

    /**
     * Delete the given comment.
     */
    public function delete(User $user, Comment $comment): ?bool
    {
        if ($user->isNot($comment->user)) {
            throw new AuthorizationException('You are not the owner of this comment.');
        }

        return $comment->delete();
    }

    /**
     * Query comments with pagination, optional post filter, and eager-loaded
     * relations to prevent N+1. Post relation is constrained to exclude
     * content-heavy fields (body, etc.) via the withoutContentFields scope.
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function query(?int $postId = null, int $perPage = 15): LengthAwarePaginator
    {
        $builder = Comment::query()
            ->with(['post' => fn ($query) => $query->withoutContentFields()])
            ->with(['post.user', 'user'])
            ->latest();

        if ($postId !== null) {
            $builder->where('post_id', $postId);
        }

        return $builder->paginate($perPage);
    }

    public function find(Comment $comment): Comment
    {
        return $comment->load(['post' => fn ($query) => $query->withoutContentFields()])
            ->load(['post.user', 'user']);
    }
}
