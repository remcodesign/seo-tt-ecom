<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CommentService
{
    /**
     * Create a new comment on the given post by the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, Post $post, array $data): Comment
    {
        return $post->comments()->create([
            'user_id' => $user->id,
            'comment' => $data['comment'],
        ]);
    }

    /**
     * Update the given comment.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Comment $comment, array $data): Comment
    {
        if ($user->isNot($comment->user)) {
            throw new AuthorizationException('You are not the owner of this comment.');
        }

        $comment->update($data);

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
     * relations to prevent N+1.
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function query(?int $postId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Comment::query()
            ->with(['post', 'user:id,name'])
            ->latest();

        if ($postId !== null) {
            $query->where('post_id', $postId);
        }

        return $query->paginate($perPage);
    }
}
