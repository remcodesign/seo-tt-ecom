<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Data\Blog\Requests\StorePostData;
use App\Data\Blog\Requests\UpdatePostData;
use App\Models\Blog\Post;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

readonly class PostService
{
    public function create(StorePostData $storePostData): Post
    {
        $user = $this->resolvePostWriter($storePostData->user_id);

        $categoryIds = $this->resolveCategoryIds($storePostData->category_ids);

        $data = [
            'title' => $storePostData->title,
            'body' => $storePostData->body,
            'published_on' => $storePostData->published_on,
            'slug' => $this->generateUniqueSlug($storePostData->title),
        ];

        $post = $user->posts()->create($data);
        $post->categories()->sync($categoryIds);

        return $post;
    }

    public function update(Post $post, UpdatePostData $updatePostData): Post
    {
        $this->resolvePostWriter($updatePostData->user_id);

        $categoryIds = $this->resolveCategoryIds($updatePostData->category_ids);

        $data = [
            'user_id' => $updatePostData->user_id,
            'title' => $updatePostData->title,
            'body' => $updatePostData->body,
            'published_on' => $updatePostData->published_on,
        ];

        if ($updatePostData->title !== $post->title) {
            $data['slug'] = $this->generateUniqueSlug($updatePostData->title, $post);
            // TODO: If slug changes and old URLs were shared externally,
            // .. consider adding a redirects table to map old slugs → new slugs (301 redirects).
        }

        $post->update($data);
        $post->categories()->sync($categoryIds);

        return $post;
    }

    /**
     * Delete the given post.
     */
    public function delete(Post $post): ?bool
    {
        return $post->delete();
    }

    /**
     * Generate a unique slug from the given title, appending a numeric suffix
     * if a collision exists. When updating, the current post is excluded from
     * the uniqueness check so its own slug is considered available.
     *
     * Tries `{-1}`, `{-2}`, `{-3}` first, then falls back to a 6-digit random
     * number. Throws if a collision still occurs.
     *
     * @param  Post|null  $post  The current post being updated, or null if creating a new post.
     */
    private function generateUniqueSlug(string $title, ?Post $post = null): string
    {
        $slug = Str::slug($title);
        $base = $slug;

        // If the slug doesn't exist, return it immediately
        if (! $this->slugExists($slug, $post)) {
            return $slug;
        }

        // Try numeric suffixes 1 through 3
        for ($counter = 1; $counter <= 3; $counter++) {
            $slug = sprintf('%s-%d', $base, $counter);

            if (! $this->slugExists($slug, $post)) {
                return $slug;
            }
        }

        // Fallback: append a 6-digit random number
        $slug = sprintf('%s-%d', $base, random_int(100_000, 999_999));

        if ($this->slugExists($slug, $post)) {
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
     *
     * @param  Post|null  $post  The current post being updated, or null if creating a new post.
     */
    private function slugExists(string $slug, ?Post $post = null): bool
    {
        $query = Post::where('slug', $slug);

        // If a current post is provided, exclude it from the uniqueness check
        if ($post instanceof Post) {
            // false here means the current post is the only one with this slug, so it's not a collision
            $query->where('id', '!=', $post->id);
        }

        return $query->exists();
    }

    /**
     * Ensure the given user ID belongs to a writer user.
     *
     * @throws \RuntimeException if the user is not a writer
     */
    private function resolvePostWriter(int $userId): User
    {
        $user = User::findOrFail($userId);

        if (! $user::isWriter($user)) {
            throw new \RuntimeException('User must have the "writer" role to create or update posts.');
        }

        return $user;
    }

    /**
     * Resolve the category IDs for a post, applying the "Uncategorized" fallback.
     *
     * If no category IDs are provided, defaults to the "Uncategorized" category.
     *
     * If the "Uncategorized" category was selected alongside real categories,
     * it is removed so that only real categories are assigned.
     *
     * (PHPStan) The "Uncategorized" category is guaranteed to exist via the CategorySeeder
     *
     * @param  array<int>  $categoryIds
     * @return array<int>
     */
    private function resolveCategoryIds(array $categoryIds): array
    {
        // todo (future) create category service to handle this logic, and move this logic to that service)
        $uncategorized = Category::firstOrCreate(
            ['slug' => 'uncategorized'],
            ['name' => '[Uncategorized]'],
        );

        if ($categoryIds === []) {
            return [$uncategorized->id];
        }

        // If a real category was also selected, remove the "Uncategorized" fallback
        if (count($categoryIds) > 1 && in_array($uncategorized->id, $categoryIds, true)) {
            return Collection::make($categoryIds)
                ->filter(static fn (int $id): bool => $id !== $uncategorized->id)
                ->values()
                ->all();
        }

        return $categoryIds;
    }

    /**
     * Query posts with pagination, optional comment loading, and order-by support, preventing N+1.
     *
     * @param  'asc'|'desc'  $orderByDirection
     * @return LengthAwarePaginator<int, Post>
     */
    public function query(
        bool $withComments = false,
        int $perPage = 15,
        string $orderByColumn = 'published_on',
        string $orderByDirection = 'desc',
    ): LengthAwarePaginator {
        $builder = Post::query()
            ->published()
            ->with('user')
            ->withCount('comments');

        if ($withComments) {
            $builder->with(['comments' => function ($query): void {
                $query->with('user');
            }]);
        }

        return $builder->orderBy($orderByColumn, $orderByDirection)->paginate($perPage);
    }

    /**
     * Load the requested post with related data to prevent N+1.
     */
    public function find(Post $post, bool $withComments = false): Post
    {
        $relations = ['user'];

        if ($withComments) {
            $relations['comments'] = function ($query): void {
                $query->with('user')->orderBy('created_at', 'desc');
            };
        }

        return $post->load($relations)->loadCount('comments');
    }
}
