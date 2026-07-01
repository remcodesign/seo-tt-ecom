<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Create a new post for the given user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Post
    {
        $data['slug'] = $this->generateUniqueSlug($data['title']);

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

        if (isset($data['title']) && $data['title'] !== $post->title) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $post);
            // TODO: If slug changes and old URLs were shared externally, consider
            // adding a redirects table to map old slugs → new slugs (301 redirects).
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
     * Generate a unique slug from the given title, appending a numeric suffix
     * if a collision exists. When updating, the current post is excluded from
     * the uniqueness check so its own slug is considered available.
     *
     * Tries `{-1}`, `{-2}`, `{-3}` first, then falls back to a 6-digit random
     * number. Throws if a collision still occurs.
     */
    private function generateUniqueSlug(string $title, ?Post $current = null): string
    {
        $slug = Str::slug($title);
        $base = $slug;

        if (! $this->slugExists($slug, $current)) {
            return $slug;
        }

        // Try numeric suffixes 1 through 3
        for ($counter = 1; $counter <= 3; $counter++) {
            $slug = sprintf('%s-%d', $base, $counter);

            if (! $this->slugExists($slug, $current)) {
                return $slug;
            }
        }

        // Fallback: append a 6-digit random number
        $slug = sprintf('%s-%d', $base, random_int(100_000, 999_999));

        if ($this->slugExists($slug, $current)) {
            throw new \RuntimeException(sprintf(
                'Unable to generate a unique slug for title "%s" after multiple attempts. Choose a more unique title.',
                $title,
            ));
        }

        return $slug;
    }

    /**
     * Check whether the given slug already exists in the database,
     * optionally excluding a specific post from the check.
     */
    private function slugExists(string $slug, ?Post $current = null): bool
    {
        $query = Post::where('slug', $slug);

        // If a current post is provided, exclude it from the uniqueness check
        if ($current instanceof Post) {
            $query->where('id', '!=', $current->id);
        }

        return $query->exists();
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
            $query->with(['comments' => function ($query): void {
                $query->withUserName();
            }]);
        }

        return $query->latest('published_on')->paginate($perPage);
    }

    /**
     * Load the requested post with related data to prevent N+1.
     */
    public function find(Post $post, bool $withComments = false): Post
    {
        $relations = ['user'];

        if ($withComments) {
            $relations['comments'] = function ($query): void {
                $query->withUserName();
            };
        }

        return $post->load($relations);
    }
}
