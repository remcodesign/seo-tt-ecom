<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostService
{
    /**
     * Create a new post for the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Post
    {
        return $user->posts()->create($data);
    }

    /**
     * Update the given post.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Post $post, array $data): Post
    {
        if ($user->isNot($post->user)) {
            throw new AuthorizationException('You are not the owner of this post.');
        }

        $post->update($data);

        return $post;
    }

    /**
     * Delete the given post.
     */
    public function delete(User $user, Post $post): ?bool
    {
        if ($user->isNot($post->user)) {
            throw new AuthorizationException('You are not the owner of this post.');
        }

        return $post->delete();
    }

    /**
     * Query posts with pagination and optional comment loading, preventing N+1.
     *
     * @return LengthAwarePaginator<int, Post>
     */
    public function query(bool $withComments = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = Post::query()->with('user');

        if ($withComments) {
            $query->with('comments.user');
        }

        return $query->latest('published_on')->paginate($perPage);
    }
}
