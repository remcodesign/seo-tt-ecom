<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

describe('PostService', function (): void {
    describe('create', function (): void {
        it('creates a post with auto-generated slug from title', function (): void {
            $user = User::factory()->create();
            $service = app(PostService::class);

            $post = $service->create($user, [
                'title' => 'My New Post',
                'body' => 'Post body content',
                'published_on' => now(),
            ]);

            expect($post)->toBeInstanceOf(Post::class)
                ->and($post->exists)->toBeTrue()
                ->and($post->user_id)->toBe($user->id)
                ->and($post->title)->toBe('My New Post')
                ->and($post->body)->toBe('Post body content')
                ->and($post->slug)->toBe('my-new-post');
        });

        it('generates a unique slug when title collides', function (): void {
            $user = User::factory()->create();
            $service = app(PostService::class);

            $first = $service->create($user, [
                'title' => 'My Post',
                'body' => 'Body',
                'published_on' => now(),
            ]);

            expect($first->slug)->toBe('my-post');

            $second = $service->create($user, [
                'title' => 'My Post',
                'body' => 'Another body',
                'published_on' => now(),
            ]);

            expect($second->slug)->toBe('my-post-1');
        });

        it('falls back to a random slug after 3 numeric collisions', function (): void {
            $user = User::factory()->create();
            $service = app(PostService::class);

            // Create 5 posts with the same title to exhaust -1, -2, -3 and reach the random fallback
            for ($i = 0; $i < 5; $i++) {
                $post = $service->create($user, [
                    'title' => 'Collision Test',
                    'body' => 'Body '.$i,
                    'published_on' => now(),
                ]);

                if ($i < 4) {
                    expect($post->slug)->toBe($i === 0 ? 'collision-test' : sprintf('collision-test-%d', $i));
                } else {
                    // Fifth post uses random fallback — should match pattern "collision-test-{6 digits}"
                    expect($post->slug)->toMatch('/^collision-test-\d{6}$/');
                }
            }
        });
    });

    describe('update', function (): void {
        it('updates a post when the authenticated user is the creator', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create(['title' => 'Original']);
            $service = app(PostService::class);

            $result = $service->update($user, $post, ['title' => 'Updated']);

            expect($result->title)->toBe('Updated');
            expect($result->slug)->toBe('updated');
            expect($post->fresh()->title)->toBe('Updated');
            expect($post->fresh()->slug)->toBe('updated');
        });

        it('keeps the existing slug when title does not change', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create(['title' => 'Original']);
            $originalSlug = $post->slug;
            $service = app(PostService::class);

            $service->update($user, $post, ['body' => 'Only body update']);

            expect($post->fresh()->slug)->toBe($originalSlug);
        });

        it('throws an exception when a non-creator tries to update', function (): void {
            $creator = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($creator)->create();
            $service = app(PostService::class);

            expect(fn () => $service->update($other, $post, ['title' => 'Hacked']))
                ->toThrow(AuthorizationException::class, 'You are not the owner of this post.');
        });
    });

    describe('delete', function (): void {
        it('deletes a post when the authenticated user is the creator', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $service = app(PostService::class);

            $service->delete($user, $post);

            expect(Post::find($post->id))->toBeNull();
        });

        it('throws an exception when a non-creator tries to delete', function (): void {
            $creator = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($creator)->create();
            $service = app(PostService::class);

            expect(fn () => $service->delete($other, $post))
                ->toThrow(AuthorizationException::class, 'You are not the owner of this post.');
        });
    });

    describe('query', function (): void {
        it('returns paginated posts with user eager-loaded', function (): void {
            Post::factory()->count(5)->for(User::factory())->create();
            $service = app(PostService::class);

            $result = $service->query(perPage: 3);

            expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result->total())->toBe(5)
                ->and($result->perPage())->toBe(3)
                ->and($result->items())->toHaveCount(3);
            expect($result->first()->relationLoaded('user'))->toBeTrue();
        });

        it('eager-loads comments when withComments is true', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            Comment::factory()->count(2)->for($post)->for($user)->create();
            $service = app(PostService::class);

            $result = $service->query(withComments: true, perPage: 15);

            $loadedPost = $result->first();
            expect($loadedPost->relationLoaded('comments'))->toBeTrue();
            expect($loadedPost->comments)->toHaveCount(2);

            $comment = $loadedPost->comments->first();
            expect($comment->relationLoaded('user'))->toBeTrue();
        });

        it('loads only comment user id and name when withComments is true', function (): void {
            $user = User::factory()->create(['name' => 'Jane Doe']);
            $post = Post::factory()->for($user)->create();
            Comment::factory()->count(2)->for($post)->for($user)->create();
            $service = app(PostService::class);

            $result = $service->query(withComments: true, perPage: 15);

            $comment = $result->first()->comments->first();

            expect($comment->user->toArray())->toBe([
                'id' => $user->id,
                'name' => 'Jane Doe',
            ]);
        });
    });
});
