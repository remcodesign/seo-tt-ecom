<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Post', function (): void {
    describe('Configuration & Data Integrity', function (): void {
        it('creates a post using the factory', function (): void {
            $post = Post::factory()->create();

            expect($post)->toBeInstanceOf(Post::class);
            expect($post->exists)->toBeTrue();
            expect($post->published_on)->toBeInstanceOf(Carbon::class);
            expect($post->body)->toBeString();
            expect($post->toArray())->toHaveKey('title');
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
            Comment::factory()->count(3)->for($post)->create();

            expect($post->comments)->toHaveCount(3);
            expect($post->comments->first())->toBeInstanceOf(Comment::class);
        });
    });

    describe('Database Constraints & Rules', function (): void {
        it('throws when saving a post without a user', function (): void {
            $post = new Post(['title' => 'Test', 'body' => 'Body text', 'slug' => 'test', 'published_on' => now()]);

            expect(fn () => $post->save())->toThrow(QueryException::class);
        });

        it('deletes comments when the post is deleted', function (): void {
            $post = Post::factory()->for(User::factory())->create();
            Comment::factory()->count(2)->for($post)->create();

            $post->delete();

            expect($post->comments()->count())->toBe(0);
        });
    });
});
