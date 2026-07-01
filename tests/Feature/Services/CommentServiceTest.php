<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\Blog\CommentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

describe('CommentService', function (): void {
    describe('create', function (): void {
        it('creates a comment on a post for the given user', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $service = app(CommentService::class);

            $comment = $service->create($user, $post, [
                'comment' => 'Great post!',
            ]);

            expect($comment)->toBeInstanceOf(Comment::class)
                ->and($comment->exists)->toBeTrue()
                ->and($comment->post_id)->toBe($post->id)
                ->and($comment->user_id)->toBe($user->id)
                ->and($comment->comment)->toBe('Great post!');
        });
    });

    describe('update', function (): void {
        it('updates a comment when the authenticated user is the creator', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $comment = Comment::factory()->for($post)->for($user)->create(['comment' => 'Original']);
            $service = app(CommentService::class);

            $result = $service->update($user, $comment, ['comment' => 'Updated!']);

            expect($result->comment)->toBe('Updated!');
            expect($comment->fresh()->comment)->toBe('Updated!');
        });

        it('throws an exception when a non-creator tries to update', function (): void {
            $creator = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($creator)->create();
            $comment = Comment::factory()->for($post)->for($creator)->create();
            $service = app(CommentService::class);

            expect(fn () => $service->update($other, $comment, ['comment' => 'Hacked']))
                ->toThrow(AuthorizationException::class, 'You are not the owner of this comment.');
        });
    });

    describe('delete', function (): void {
        it('deletes a comment when the authenticated user is the creator', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $comment = Comment::factory()->for($post)->for($user)->create();
            $service = app(CommentService::class);

            $service->delete($user, $comment);

            expect(Comment::find($comment->id))->toBeNull();
        });

        it('throws an exception when a non-creator tries to delete', function (): void {
            $creator = User::factory()->create();
            $other = User::factory()->create();
            $post = Post::factory()->for($creator)->create();
            $comment = Comment::factory()->for($post)->for($creator)->create();
            $service = app(CommentService::class);

            expect(fn () => $service->delete($other, $comment))
                ->toThrow(AuthorizationException::class, 'You are not the owner of this comment.');
        });
    });

    describe('query', function (): void {
        it('returns paginated comments with post and user eager-loaded', function (): void {
            Post::factory()->count(3)->for(User::factory())->create()->each(function (Post $post): void {
                Comment::factory()->count(2)->for($post)->for(User::factory())->create();
            });
            $service = app(CommentService::class);

            $result = $service->query(perPage: 4);

            expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($result->total())->toBe(6)
                ->and($result->perPage())->toBe(4)
                ->and($result->items())->toHaveCount(4);

            $loaded = $result->first();
            expect($loaded->relationLoaded('post'))->toBeTrue();
            expect($loaded->relationLoaded('user'))->toBeTrue();
        });

        it('filters comments by post id', function (): void {
            $user = User::factory()->create();
            $postA = Post::factory()->for($user)->create();
            $postB = Post::factory()->for($user)->create();
            Comment::factory()->count(3)->for($postA)->for($user)->create();
            Comment::factory()->count(2)->for($postB)->for($user)->create();
            $service = app(CommentService::class);

            $result = $service->query(postId: $postA->id, perPage: 15);

            expect($result->total())->toBe(3);
            foreach ($result->items() as $comment) {
                expect($comment->post_id)->toBe($postA->id);
            }
        });

        it('loads only id and name for the comment user', function (): void {
            $user = User::factory()->create(['name' => 'Commenter Name']);
            $post = Post::factory()->for($user)->create();
            Comment::factory()->for($post)->for($user)->create();
            $service = app(CommentService::class);

            $result = $service->query(perPage: 15);

            $comment = $result->first();
            expect($comment->user->toArray())->toBe([
                'id' => $user->id,
                'name' => 'Commenter Name',
            ]);
        });
    });
});
