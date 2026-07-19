<?php

declare(strict_types=1);

namespace App\Services\Blog {
    function random_int(int $min, int $max): int
    {
        return 123456;
    }
}

namespace {

    use App\Data\Blog\Requests\StorePostData;
    use App\Data\Blog\Requests\UpdatePostData;
    use App\Models\Blog\Comment;
    use App\Models\Blog\Post;
    use App\Models\User;
    use App\Services\Blog\PostService;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Pagination\LengthAwarePaginator;

    uses(RefreshDatabase::class);

    describe('PostService', function (): void {
        describe('create', function (): void {
            it('creates a post with auto-generated slug from title', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $postService = app(PostService::class);

                $post = $postService->create(new StorePostData(
                    user_id: $user->id,
                    title: 'My New Post',
                    body: 'Post body content',
                    published_on: now()->toImmutable(),
                ));

                expect($post)->toBeInstanceOf(Post::class)
                    ->and($post->exists)->toBeTrue()
                    ->and($post->user_id)->toBe($user->id)
                    ->and($post->title)->toBe('My New Post')
                    ->and($post->body)->toBe('Post body content')
                    ->and($post->slug)->toBe('my-new-post');
            });

            it('generates a unique slug when title collides', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $postService = app(PostService::class);

                $post = $postService->create(new StorePostData(
                    user_id: $user->id,
                    title: 'My Post',
                    body: 'Body',
                    published_on: now()->toImmutable(),
                ));

                expect($post->slug)->toBe('my-post');

                $second = $postService->create(new StorePostData(
                    user_id: $user->id,
                    title: 'My Post',
                    body: 'Another body',
                    published_on: now()->toImmutable(),
                ));

                expect($second->slug)->toBe('my-post-1');
            });

            it('falls back to a random slug after 3 numeric collisions', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $postService = app(PostService::class);

                for ($i = 0; $i < 5; $i++) {
                    $post = $postService->create(new StorePostData(
                        user_id: $user->id,
                        title: 'Collision Test',
                        body: 'Body '.$i,
                        published_on: now()->toImmutable(),
                    ));

                    if ($i < 4) {
                        expect($post->slug)->toBe($i === 0 ? 'collision-test' : sprintf('collision-test-%d', $i));
                    } else {
                        expect($post->slug)->toMatch('/^collision-test-\d{6}$/');
                    }
                }
            });

            it('throws when a random fallback collides after multiple attempts', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $postService = app(PostService::class);

                Post::factory()->for($user)->create(['title' => 'Collision Test', 'slug' => 'collision-test-123456']);

                for ($i = 0; $i < 4; $i++) {
                    $postService->create(new StorePostData(
                        user_id: $user->id,
                        title: 'Collision Test',
                        body: 'Body '.$i,
                        published_on: now()->toImmutable(),
                    ));
                }

                expect(fn () => $postService->create(new StorePostData(
                    user_id: $user->id,
                    title: 'Collision Test',
                    body: 'Final body',
                    published_on: now()->toImmutable(),
                )))->toThrow(RuntimeException::class, 'Unable to generate a unique slug for title "Collision Test" after multiple attempts. Choose a more unique title.');
            });
        });

        describe('update', function (): void {
            it('updates a post with valid data', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $post = Post::factory()->for($user)->create(['title' => 'Original']);
                $postService = app(PostService::class);

                $result = $postService->update($post, new UpdatePostData(
                    user_id: $user->id,
                    title: 'Updated',
                ));

                expect($result->title)->toBe('Updated');
                expect($result->slug)->toBe('updated');
                expect($post->fresh()->title)->toBe('Updated');
                expect($post->fresh()->slug)->toBe('updated');
            });

            it('keeps the existing slug when title does not change', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $post = Post::factory()->for($user)->create(['title' => 'Original']);
                $originalSlug = $post->slug;
                $postService = app(PostService::class);

                $postService->update($post, new UpdatePostData(
                    user_id: $user->id,
                    title: 'Original',
                    body: 'Only body update',
                ));

                expect($post->fresh()->slug)->toBe($originalSlug);
            });
        });

        describe('delete', function (): void {
            it('deletes a post', function (): void {
                $user = User::factory()->create(['role_label' => 'writer']);
                $post = Post::factory()->for($user)->create();
                $postService = app(PostService::class);

                $postService->delete($post);

                expect(Post::find($post->id))->toBeNull();
            });
        });

        describe('query', function (): void {
            it('returns paginated posts with user eager-loaded', function (): void {
                Post::factory()->count(5)->for(User::factory())->create();
                $postService = app(PostService::class);

                $lengthAwarePaginator = $postService->query(perPage: 3);

                expect($lengthAwarePaginator)->toBeInstanceOf(LengthAwarePaginator::class)
                    ->and($lengthAwarePaginator->total())->toBe(5)
                    ->and($lengthAwarePaginator->perPage())->toBe(3)
                    ->and($lengthAwarePaginator->items())->toHaveCount(3);
                expect($lengthAwarePaginator->first()->relationLoaded('user'))->toBeTrue();
            });

            it('eager-loads comments when withComments is true', function (): void {
                $user = User::factory()->create();
                $post = Post::factory()->for($user)->create();
                Comment::factory()->count(2)->for($post)->for($user)->create();
                $postService = app(PostService::class);

                $lengthAwarePaginator = $postService->query(withComments: true, perPage: 15);

                $loadedPost = $lengthAwarePaginator->first();
                expect($loadedPost->relationLoaded('comments'))->toBeTrue();
                expect($loadedPost->comments)->toHaveCount(2);

                $comment = $loadedPost->comments->first();
                expect($comment->relationLoaded('user'))->toBeTrue();
            });

            it('applies custom orderBy column and direction', function (): void {
                $user = User::factory()->create();
                $older = Post::factory()->for($user)->create(['updated_at' => now()->subDays(2)]);
                $newer = Post::factory()->for($user)->create(['updated_at' => now()->subDay()]);
                $postService = app(PostService::class);

                $lengthAwarePaginator = $postService->query(
                    withComments: false,
                    perPage: 15,
                    orderByColumn: 'updated_at',
                    orderByDirection: 'asc',
                );

                $ids = $lengthAwarePaginator->pluck('id')->all();
                expect($ids)->toBe([$older->id, $newer->id]);
            });

            it('orders by multiple column-direction combinations', function (): void {
                $user = User::factory()->create();
                $latest = Post::factory()->for($user)->create(['updated_at' => now()]);
                $older = Post::factory()->for($user)->create(['updated_at' => now()->subDay()]);
                $postService = app(PostService::class);

                $lengthAwarePaginator = $postService->query(
                    withComments: false,
                    perPage: 15,
                    orderByColumn: 'updated_at',
                    orderByDirection: 'desc',
                );

                $ids = $lengthAwarePaginator->pluck('id')->all();
                expect($ids)->toBe([$latest->id, $older->id]);
            });

        });
    });
}
