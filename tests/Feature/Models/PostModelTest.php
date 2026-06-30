<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Carbon;

describe('Configuration & Data Integrity', function (): void {
    it('creates a post using the factory', function (): void {
        $post = Post::factory()->create();

        expect($post->exists)->toBeTrue();
        expect($post->published_on)->toBeInstanceOf(Carbon::class);
    });
});

describe('Relationship Integrity', function (): void {
    it('belongs to a user', function (): void {
        $post = Post::factory()->for(User::factory())->create();

        expect($post->user)->toBeInstanceOf(User::class);
        expect($post->user_id)->toBe($post->user->id);
    });

    it('has many comments', function (): void {
        $post = Post::factory()->create();
        Comment::factory()->for($post)->create();
        Comment::factory()->for($post)->create();

        expect($post->comments)->toHaveCount(2);
        expect($post->comments->first())->toBeInstanceOf(Comment::class);
    });
});

describe('Database Constraints & Rules', function (): void {
    it('is deleted when the owning user is deleted', function (): void {
        $post = Post::factory()->for(User::factory())->create();
        Comment::factory()->for($post)->create();

        $post->user->delete();

        expect(Post::find($post->id))->toBeNull();
    });
});
