<?php

declare(strict_types=1);

use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PostController (API)', function (): void {
    describe('index', function (): void {
        it('returns paginated posts for the public', function (): void {
            Post::factory()->count(5)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/posts');

            // dump($response->json());

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'body',
                            'slug',
                            'user_id',
                            'user' => ['id', 'name'],
                        ],
                    ],
                    'meta',
                    'links',
                ])
                ->assertJsonCount(5, 'data');
        });

        it('does not include draft posts in the public list', function (): void {
            // Create three published posts
            Post::factory()->count(3)->for(User::factory())->create();
            // Create a draft post (published_on is null)
            Post::factory()->for(User::factory())->create(['published_on' => null]);

            $response = $this->getJson('/api/blog/posts');

            $response->assertSuccessful()
                ->assertJsonCount(3, 'data');
        });
    });

    describe('show', function (): void {
        it('returns a single post for the public', function (): void {
            $post = Post::factory()->for(User::factory())->create();
            $comment = Comment::factory()->for($post)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/posts/'.$post->slug);

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'id',
                    'title',
                    'body',
                    'slug',
                    'user_id',
                    'user' => ['id', 'name'],
                    'comments' => [
                        '*' => [
                            'id',
                            'post_id',
                            'user_id',
                            'comment',
                            'user' => ['id', 'name'],
                        ],
                    ],
                ])
                ->assertJsonPath('comments.0.id', $comment->id);
        });

        it('returns 404 for draft posts', function (): void {
            $post = Post::factory()->for(User::factory())->create(['published_on' => null]);

            $this->getJson('/api/blog/posts/'.$post->slug)
                ->assertNotFound();
        });
    });

    describe('store', function (): void {
        it('creates a new post for an authenticated user', function (): void {
            Sanctum::actingAs($user = User::factory()->create());

            $response = $this->postJson('/api/blog/posts', [
                'title' => 'My New Post',
                'body' => 'This is the body content.',
                'published_on' => now()->toDateTimeString(),
            ]);

            $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'title',
                    'body',
                    'slug',
                    'user_id',
                ])
                ->assertJson([
                    'title' => 'My New Post',
                    'body' => 'This is the body content.',
                    'user_id' => $user->id,
                ]);
        });

        it('rejects unauthenticated requests for store', function (): void {
            $this->postJson('/api/blog/posts', [
                'title' => 'My Post',
            ])->assertUnauthorized();
        });

        it('fails validation when title is missing', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $this->postJson('/api/blog/posts', ['body' => 'No title'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });
    });

    describe('update', function (): void {
        it('updates a post when the authenticated user is the owner', function (): void {
            Sanctum::actingAs($user = User::factory()->create());
            $post = Post::factory()->for($user)->create(['title' => 'Original Title']);

            $response = $this->putJson('/api/blog/posts/'.$post->id, [
                'title' => 'Updated Title',
            ]);

            $response->assertSuccessful()
                ->assertJson([
                    'title' => 'Updated Title',
                ]);
            expect($post->fresh()->title)->toBe('Updated Title');
        });

        it('rejects updates from a non-owner', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($owner)->create();

            Sanctum::actingAs($other);

            $this->putJson('/api/blog/posts/'.$post->id, ['title' => 'Hacked'])
                ->assertForbidden();
        });

    });

    describe('destroy', function (): void {
        it('deletes a post when the authenticated user is the owner', function (): void {
            Sanctum::actingAs($user = User::factory()->create());
            $post = Post::factory()->for($user)->create();

            $this->deleteJson('/api/blog/posts/'.$post->id)
                ->assertNoContent();

            expect(Post::find($post->id))->toBeNull();
        });

        it('rejects deletion from a non-owner', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($owner)->create();

            Sanctum::actingAs($other);

            $this->deleteJson('/api/blog/posts/'.$post->id)
                ->assertForbidden();
        });
    });
});
