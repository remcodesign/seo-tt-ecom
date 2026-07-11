<?php

declare(strict_types=1);

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\UpdateCommentData;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use App\Services\Blog\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

describe('CommentService', function (): void {
    describe('create', function (): void {
        it('creates a comment on a post for the given user', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $commentService = app(CommentService::class);

            $comment = $commentService->create($user, $post, new StoreCommentData(
                post_id: $post->id,
                comment: 'Great post!',
            ));

            expect($comment)->toBeInstanceOf(Comment::class)
                ->and($comment->exists)->toBeTrue()
                ->and($comment->post_id)->toBe($post->id)
                ->and($comment->user_id)->toBe($user->id)
                ->and($comment->comment)->toBe('Great post!');
        });
    });

    describe('update', function (): void {
        it('updates a comment with valid data', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $comment = Comment::factory()->for($post)->for($user)->create(['comment' => 'Original']);
            $commentService = app(CommentService::class);

            $result = $commentService->update($user, $comment, new UpdateCommentData(
                comment: 'Updated!',
            ));

            expect($result->comment)->toBe('Updated!');
            expect($comment->fresh()->comment)->toBe('Updated!');
        });
    });

    describe('delete', function (): void {
        it('deletes a comment', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $comment = Comment::factory()->for($post)->for($user)->create();
            $commentService = app(CommentService::class);

            $commentService->delete($user, $comment);

            expect(Comment::find($comment->id))->toBeNull();
        });
    });

    describe('query', function (): void {
        it('returns paginated comments with post and user eager-loaded', function (): void {
            Post::factory()->count(3)->for(User::factory())->create()->each(function (Post $post): void {
                Comment::factory()->count(2)->for($post)->for(User::factory())->create();
            });
            $commentService = app(CommentService::class);

            $lengthAwarePaginator = $commentService->query(perPage: 4);

            expect($lengthAwarePaginator)->toBeInstanceOf(LengthAwarePaginator::class)
                ->and($lengthAwarePaginator->total())->toBe(6)
                ->and($lengthAwarePaginator->perPage())->toBe(4)
                ->and($lengthAwarePaginator->items())->toHaveCount(4);

            $loaded = $lengthAwarePaginator->first();
            expect($loaded->relationLoaded('post'))->toBeTrue();
            expect($loaded->relationLoaded('user'))->toBeTrue();
        });

        it('filters comments by post id', function (): void {
            $user = User::factory()->create();
            $postA = Post::factory()->for($user)->create();
            $postB = Post::factory()->for($user)->create();
            Comment::factory()->count(3)->for($postA)->for($user)->create();
            Comment::factory()->count(2)->for($postB)->for($user)->create();
            $commentService = app(CommentService::class);

            $lengthAwarePaginator = $commentService->query(postId: $postA->id, perPage: 15);

            expect($lengthAwarePaginator->total())->toBe(3);
            foreach ($lengthAwarePaginator->items() as $comment) {
                expect($comment->post_id)->toBe($postA->id);
            }
        });

        it('applies custom orderBy column and direction', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $older = Comment::factory()->for($post)->for($user)->create(['created_at' => now()->subDays(2)]);
            $newer = Comment::factory()->for($post)->for($user)->create(['created_at' => now()->subDay()]);
            $commentService = app(CommentService::class);

            $lengthAwarePaginator = $commentService->query(
                perPage: 15,
                orderByColumn: 'created_at',
                orderByDirection: 'asc',
            );

            $ids = $lengthAwarePaginator->pluck('id')->all();
            expect($ids)->toBe([$older->id, $newer->id]);
        });

        it('orders by desc direction', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $newer = Comment::factory()->for($post)->for($user)->create(['created_at' => now()->subDay()]);
            $older = Comment::factory()->for($post)->for($user)->create(['created_at' => now()->subDays(2)]);
            $commentService = app(CommentService::class);

            $lengthAwarePaginator = $commentService->query(
                perPage: 15,
                orderByColumn: 'created_at',
                orderByDirection: 'desc',
            );

            $ids = $lengthAwarePaginator->pluck('id')->all();
            expect($ids)->toBe([$newer->id, $older->id]);
        });
    });
});
