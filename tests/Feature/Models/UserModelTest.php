<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Configuration & Data Integrity', function (): void {
    it('creates a user using the factory', function (): void {
        $user = User::factory()->create();

        expect($user->exists)->toBeTrue();
        expect($user->email)->not->toBeEmpty();
    });

    it('hashes the password when it is set', function (): void {
        $user = User::factory()->create(['password' => 'secret']);

        expect($user->password)->not->toBe('secret');
        expect(Hash::check('secret', $user->password))->toBeTrue();
    });
});

describe('Relationship Integrity', function (): void {
    it('loads posts and comments relationships', function (): void {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();
        $comment = Comment::factory()->for($user)->for($post)->create();

        expect($user->posts)->toHaveCount(1);
        expect($user->posts->first())->toBeInstanceOf(Post::class);
        expect($user->comments)->toHaveCount(1);
        expect($user->comments->first())->toBeInstanceOf(Comment::class);
        expect($user->comments->first()->post_id)->toBe($post->id);
    });
});

describe('Database Constraints & Rules', function (): void {
    it('deletes related comments when the user is deleted', function (): void {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();
        $comment = Comment::factory()->for($user)->for($post)->create();

        $user->delete();

        expect(Comment::find($comment->id))->toBeNull();
    });
});
