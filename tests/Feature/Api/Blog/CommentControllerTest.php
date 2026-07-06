<?php

declare(strict_types=1);

use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('CommentController (API)', function (): void {
    describe('index', function (): void {
        it('returns paginated comments for the public', function (): void {
            Comment::factory()->count(5)->for(Post::factory()->for(User::factory()))->create();

            $response = $this->getJson('/api/blog/comments');

            // dump($response->json());

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'post_id',
                            'user_id',
                            'comment',
                            'post' => [
                                'id',
                                'title',
                                'user' => ['id', 'name', 'email'],
                            ],
                            'user' => ['id', 'name', 'email'],
                        ],
                    ],
                    'meta',
                    'links',
                ])
                ->assertJsonCount(5, 'data');
        });

        it('filters comments by post id', function (): void {
            $user = User::factory()->create();
            $postA = Post::factory()->for($user)->create();
            $postB = Post::factory()->for($user)->create();

            Comment::factory()->count(3)->for($postA)->for(User::factory())->create();
            Comment::factory()->count(2)->for($postB)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/comments?post_id='.$postA->id);

            $response->assertSuccessful()
                ->assertJsonCount(3, 'data');

            foreach ($response->json('data') as $item) {
                expect($item['post_id'])->toBe($postA->id);
            }
        });

        it('does not include comments on unpublished posts', function (): void {
            $user = User::factory()->create();
            $publishedPost = Post::factory()->for($user)->create();
            $draftPost = Post::factory()->for($user)->create(['published_on' => null]);

            Comment::factory()->count(2)->for($publishedPost)->for(User::factory())->create();
            Comment::factory()->count(2)->for($draftPost)->for(User::factory())->create();

            $response = $this->getJson('/api/blog/comments');

            $response->assertSuccessful()
                ->assertJsonCount(2, 'data');
        });
    });

    describe('show', function (): void {
        it('returns a single comment for the public', function (): void {
            $comment = Comment::factory()->for(Post::factory()->for(User::factory()))->create();

            $response = $this->getJson('/api/blog/comments/'.$comment->id);

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'id',
                    'post_id',
                    'user_id',
                    'comment',
                    'post' => [
                        'id',
                        'title',
                        'user' => ['id', 'name', 'email'],
                    ],
                    'user' => ['id', 'name', 'email'],
                ]);
        });

        it('returns 404 for a comment on an unpublished post', function (): void {
            $comment = Comment::factory()->for(Post::factory()->for(User::factory())->create(['published_on' => null]))->create();

            $this->getJson('/api/blog/comments/'.$comment->id)
                ->assertNotFound();
        });
    });

    describe('store', function (): void {
        it('creates a new comment for an authenticated user', function (): void {
            Sanctum::actingAs($user = User::factory()->create());
            $post = Post::factory()->for($user)->create();

            $response = $this->postJson('/api/blog/comments', [
                'post_id' => $post->id,
                'comment' => 'Great post!',
            ]);

            $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'post_id',
                    'user_id',
                    'comment',
                ])
                ->assertJson([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'comment' => 'Great post!',
                ]);
        });

        it('rejects unauthenticated requests for store', function (): void {
            $post = Post::factory()->for(User::factory())->create();

            $this->postJson('/api/blog/comments', [
                'post_id' => $post->id,
                'comment' => 'Nice work',
            ])->assertUnauthorized();
        });

        it('fails validation when comment is missing', function (): void {
            Sanctum::actingAs(User::factory()->create());
            $post = Post::factory()->for(User::factory())->create();

            $this->postJson('/api/blog/comments', ['post_id' => $post->id])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['comment']);
        });
    });

    describe('update', function (): void {
        it('updates a comment when the authenticated user is the owner', function (): void {
            $user = User::factory()->create();
            Sanctum::actingAs($user);
            $comment = Comment::factory()->for(Post::factory()->for($user))->for($user)->create(['comment' => 'Original']);

            $response = $this->putJson('/api/blog/comments/'.$comment->id, ['comment' => 'Updated!']);

            $response->assertSuccessful()
                ->assertJson(['comment' => 'Updated!']);
            expect($comment->fresh()->comment)->toBe('Updated!');
        });

        it('rejects updates from a non-owner', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $comment = Comment::factory()->for(Post::factory()->for($owner))->for($owner)->create();
            Sanctum::actingAs($other);

            $this->putJson('/api/blog/comments/'.$comment->id, ['comment' => 'Hacked'])
                ->assertForbidden();
        });
    });

    describe('destroy', function (): void {
        it('deletes a comment when the authenticated user is the owner', function (): void {
            $user = User::factory()->create();
            Sanctum::actingAs($user);
            $comment = Comment::factory()->for(Post::factory()->for($user))->for($user)->create();

            $this->deleteJson('/api/blog/comments/'.$comment->id)
                ->assertNoContent();

            expect(Comment::find($comment->id))->toBeNull();
        });

        it('rejects deletion from a non-owner', function (): void {
            $owner = User::factory()->create();
            $other = User::factory()->create();
            $comment = Comment::factory()->for(Post::factory()->for($owner))->for($owner)->create();
            Sanctum::actingAs($other);

            $this->deleteJson('/api/blog/comments/'.$comment->id)
                ->assertForbidden();
        });
    });
});
