<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

describe('Configuration & Data Integrity', function (): void {
    it('creates a comment using the factory', function (): void {
        $comment = Comment::factory()->create();

        expect($comment->exists)->toBeTrue();
        expect($comment->comment)->not->toBeEmpty();
    });
});

describe('Relationship Integrity', function (): void {
    it('belongs to a user and a post', function (): void {
        $comment = Comment::factory()
            ->for(User::factory())
            ->for(Post::factory())
            ->create();

        expect($comment->user)->toBeInstanceOf(User::class);
        expect($comment->post)->toBeInstanceOf(Post::class);
        expect($comment->user_id)->toBe($comment->user->id);
        expect($comment->post_id)->toBe($comment->post->id);
    });
});

describe('Database Constraints & Rules', function (): void {
    it('is deleted when the owning user is deleted', function (): void {
        $comment = Comment::factory()
            ->for(User::factory())
            ->for(Post::factory())
            ->create();

        $comment->user->delete();

        expect(Comment::find($comment->id))->toBeNull();
    });
});
