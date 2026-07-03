<?php

declare(strict_types=1);

use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PostController (API)', function (): void {
    describe('index', function (): void {
        it('returns paginated posts for an authenticated user', function (): void {
            Sanctum::actingAs(User::factory()->create());
            Post::factory()->count(5)->for(User::factory())->create();

            $response = $this->getJson('/api/posts');

            $response->assertSuccessful()
                ->assertJsonStructure(['data', 'meta', 'links'])
                ->assertJsonCount(5, 'data');
        });

        it('rejects unauthenticated requests', function (): void {
            $this->getJson('/api/posts')->assertUnauthorized();
        });
    });

    describe('show', function (): void {
        it('returns a single post for an authenticated user', function (): void {
            Sanctum::actingAs(User::factory()->create());
            $post = Post::factory()->for(User::factory())->create();

            $response = $this->getJson('/api/posts/'.$post->id);

            $response->assertSuccessful()
                ->assertJsonStructure(['data', 'data' => ['id', 'title', 'body', 'slug']]);
        });
    });

    describe('store', function (): void {
        it('creates a new post for an authenticated user', function (): void {
            Sanctum::actingAs($user = User::factory()->create());

            $response = $this->postJson('/api/posts', [
                'title' => 'My New Post',
                'body' => 'This is the body content.',
                'published_on' => now()->toDateTimeString(),
            ]);

            $response->assertCreated()
                ->assertJsonStructure(['id', 'title', 'body', 'slug', 'user_id'])
                ->assertJson([
                    'title' => 'My New Post',
                    'body' => 'This is the body content.',
                    'user_id' => $user->id,
                ]);
        });

        it('rejects unauthenticated requests for store', function (): void {
            $this->postJson('/api/posts', [
                'title' => 'My Post',
            ])->assertUnauthorized();
        });

        it('fails validation when title is missing', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $this->postJson('/api/posts', ['body' => 'No title'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['title']);
        });
    });

    describe('update', function (): void {
        it('updates a post when the authenticated user is the owner', function (): void {
            Sanctum::actingAs($user = User::factory()->create());
            $post = Post::factory()->for($user)->create(['title' => 'Original Title']);

            $response = $this->putJson('/api/posts/'.$post->id, [
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

            $this->putJson('/api/posts/'.$post->id, ['title' => 'Hacked'])
                ->assertForbidden();
        });

    });

    describe('destroy', function (): void {
        it('deletes a post when the authenticated user is the owner', function (): void {
            Sanctum::actingAs($user = User::factory()->create());
            $post = Post::factory()->for($user)->create();

            $this->deleteJson('/api/posts/'.$post->id)
                ->assertNoContent();

            expect(Post::find($post->id))->toBeNull();
        });

        it('rejects deletion from a non-owner', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($owner)->create();

            Sanctum::actingAs($other);

            $this->deleteJson('/api/posts/'.$post->id)
                ->assertForbidden();
        });
    });
});
