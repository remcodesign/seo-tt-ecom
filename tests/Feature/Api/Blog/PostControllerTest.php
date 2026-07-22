<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PostController (API)', function (): void {
    describe('index', function (): void {
        it('returns paginated posts for the public with the default page size', function (): void {
            Post::factory()->count(5)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/posts');

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
                ]);

            $response->assertJsonPath('meta.per_page', 6)
                ->assertJsonCount(5, 'data')
                ->assertJsonPath('meta.total', 5);
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

        it('respects the per_page query parameter', function (): void {
            Post::factory()->count(10)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/posts?per_page=4');

            $response->assertSuccessful()
                ->assertJsonPath('meta.per_page', 4)
                ->assertJsonCount(4, 'data')
                ->assertJsonPath('meta.total', 10);
        });

        it('clamps per_page to the maximum allowed value', function (): void {
            Post::factory()->count(30)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/posts?per_page=999');

            $response->assertSuccessful()
                ->assertJsonPath('meta.per_page', 12)
                ->assertJsonCount(12, 'data');
        });

        it('clamps per_page to a minimum of 1', function (): void {
            Post::factory()->count(5)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/posts?per_page=0');

            $response->assertSuccessful()
                ->assertJsonPath('meta.per_page', 1)
                ->assertJsonCount(1, 'data');
        });

        it('orders by updated_at when orderby parameter is set', function (): void {
            $user = User::factory()->create();
            $older = Post::factory()->for($user)->create(['updated_at' => now()->subDays(2)]);
            $newer = Post::factory()->for($user)->create(['updated_at' => now()->subDay()]);
            $latest = Post::factory()->for($user)->create(['updated_at' => now()]);

            $response = $this->getJson('/api/blog/posts?orderby=updated_at');

            $response->assertSuccessful();

            $ids = collect($response->json('data'))->pluck('id')->all();
            expect($ids)->toBe([$older->id, $newer->id, $latest->id]);
        });

        it('orders by updated_at desc when orderby has _desc suffix', function (): void {
            $user = User::factory()->create();
            $latest = Post::factory()->for($user)->create(['updated_at' => now()]);
            $older = Post::factory()->for($user)->create(['updated_at' => now()->subDay()]);

            $response = $this->getJson('/api/blog/posts?orderby=updated_at_desc');

            $response->assertSuccessful();

            $ids = collect($response->json('data'))->pluck('id')->all();
            expect($ids)->toBe([$latest->id, $older->id]);
        });

        it('falls back to default ordering for an invalid orderby value', function (): void {
            Post::factory()->count(3)->for(User::factory())->create();

            $this->getJson('/api/blog/posts?orderby=invalid_column')
                ->assertSuccessful()
                ->assertJsonPath('meta.total', 3);
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
            Sanctum::actingAs($user = User::factory()->create(['role_label' => RoleLabel::writer]));
            $category = Category::factory()->create();

            $response = $this->postJson('/api/blog/posts', [
                'user_id' => $user->id,
                'category_ids' => [$category->id],
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

        it('defaults to Uncategorized when no category is provided - 1', function (): void {
            Sanctum::actingAs($user = User::factory()->create(['role_label' => RoleLabel::writer]));
            // Create the "Uncategorized" category explicitly for this test
            $uncategorized = Category::factory()->create(['slug' => 'uncategorized']);

            $response = $this->postJson('/api/blog/posts', [
                'user_id' => $user->id,
                'category_ids' => [],
                'title' => 'Uncategorized Post',
                'body' => 'Body',
            ]);

            $response->assertCreated();

            $post = Post::where('title', 'Uncategorized Post')->first();
            expect($post->categories()->first()->id)->toBe($uncategorized->id);
        });

        it('defaults to Uncategorized when no category is provided - 2', function (): void {
            Sanctum::actingAs($user = User::factory()->create(['role_label' => RoleLabel::writer]));
            // no creation of the "Uncategorized" category, the system should create it automatically
            $response = $this->postJson('/api/blog/posts', [
                'user_id' => $user->id,
                'category_ids' => [],
                'title' => 'Uncategorized Post',
                'body' => 'Body',
            ]);

            $response->assertCreated();

            $post = Post::where('title', 'Uncategorized Post')->first();
            expect($post->categories()->first()->slug)->toBe('uncategorized');
        });
    });

    describe('update', function (): void {
        it('updates a post when the authenticated user is the owner', function (): void {
            Sanctum::actingAs($user = User::factory()->create(['role_label' => RoleLabel::writer]));
            $post = Post::factory()->for($user)->create(['title' => 'Original Title']);
            $category = Category::factory()->create();

            $response = $this->putJson('/api/blog/posts/'.$post->id, [
                'user_id' => $user->id,
                'category_ids' => [$category->id],
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
            $category = Category::factory()->create();

            Sanctum::actingAs($other);

            $this->putJson('/api/blog/posts/'.$post->id, ['user_id' => $other->id, 'category_ids' => [$category->id], 'title' => 'Hacked'])
                ->assertForbidden();
        });

        it('allows an admin and the post owner to update any post', function (): void {
            $owner = User::factory()->create(['role_label' => RoleLabel::writer]);
            $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
            $post = Post::factory()->for($owner)->create(['title' => 'Original']);
            $category = Category::factory()->create();

            Sanctum::actingAs($admin);

            $this->putJson('/api/blog/posts/'.$post->id, ['user_id' => $owner->id, 'category_ids' => [$category->id], 'title' => 'Admin Updated'])
                ->assertSuccessful()
                ->assertJson(['title' => 'Admin Updated']);

            expect($post->fresh()->title)->toBe('Admin Updated');
        });

    });

    describe('destroy', function (): void {
        it('deletes a post when the authenticated user is the owner', function (): void {
            Sanctum::actingAs($user = User::factory()->create(['role_label' => RoleLabel::writer]));
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

        it('allows an admin user to delete any post', function (): void {
            $owner = User::factory()->create();
            $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
            $post = Post::factory()->for($owner)->create();

            Sanctum::actingAs($admin);

            $this->deleteJson('/api/blog/posts/'.$post->id)
                ->assertNoContent();

            expect(Post::find($post->id))->toBeNull();
        });
    });
});
