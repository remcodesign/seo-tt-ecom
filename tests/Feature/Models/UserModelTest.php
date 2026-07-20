<?php

declare(strict_types=1);

use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User', function (): void {
    describe('Configuration & Data Integrity', function (): void {
        it('creates a user using the factory', function (): void {
            $user = User::factory()->create();

            expect($user)->toBeInstanceOf(User::class);
            expect($user->exists)->toBeTrue();
            expect($user->email)->not->toBeEmpty();
        });

        it('hides sensitive fields from serialization', function (): void {
            $user = User::factory()->create();

            expect($user->toArray())->not->toHaveKey('password');
            expect($user->toArray())->not->toHaveKey('remember_token');
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
            $posts = Post::factory()->count(2)->for($user)->create();
            Comment::factory()->count(3)->for($user)->for($posts->first())->create();

            expect($user->posts)->toHaveCount(2);
            expect($user->posts->first())->toBeInstanceOf(Post::class);
            expect($user->comments)->toHaveCount(3);
            expect($user->comments->first())->toBeInstanceOf(Comment::class);
        });
    });

    describe('Database Constraints & Rules', function (): void {
        it('deletes related entities when the user is deleted', function (): void {
            $user = User::factory()->create();
            $post = Post::factory()->for($user)->create();
            $comment = Comment::factory()->for($user)->for($post)->create();

            $user->delete();

            expect(Post::find($post->id))->toBeNull();
            expect(Comment::find($comment->id))->toBeNull();
        });
    });

    describe('Role Label Retrieval', function (): void {
        it('retrieves users with role_label user', function (): void {
            $user1 = User::factory()->create(['role_label' => 'user']);
            $user2 = User::factory()->create(['role_label' => 'user']);
            $admin = User::factory()->create(['role_label' => 'admin']);

            $users = User::getUsers();

            expect($users)->toHaveCount(2);
            expect($users)->toHaveKey($user1->id);
            expect($users)->toHaveKey($user2->id);
            expect($users)->not->toHaveKey($admin->id);
        });

        it('retrieves users with role_label writer', function (): void {
            $writer1 = User::factory()->create(['role_label' => 'writer']);
            $writer2 = User::factory()->create(['role_label' => 'writer']);
            $user = User::factory()->create(['role_label' => 'user']);

            $writers = User::getWriters();

            expect($writers)->toHaveCount(2);
            expect($writers)->toHaveKey($writer1->id);
            expect($writers)->toHaveKey($writer2->id);
            expect($writers)->not->toHaveKey($user->id);
        });

        it('retrieves users with role_label admin', function (): void {
            $admin1 = User::factory()->create(['role_label' => 'admin']);
            $admin2 = User::factory()->create(['role_label' => 'admin']);
            $user = User::factory()->create(['role_label' => 'user']);

            $admins = User::getAdmins();

            expect($admins)->toHaveCount(2);
            expect($admins)->toHaveKey($admin1->id);
            expect($admins)->toHaveKey($admin2->id);
            expect($admins)->not->toHaveKey($user->id);
        });
    });

    describe('Role Label Checks', function (): void {
        it('checks if a user is a writer', function (): void {
            $writer = User::factory()->create(['role_label' => 'writer']);
            $user = User::factory()->create(['role_label' => 'user']);

            expect(User::isWriter($writer))->toBeTrue();
            expect(User::isWriter($user))->toBeFalse();
        });

        it('checks if a user is an admin', function (): void {
            $admin = User::factory()->create(['role_label' => 'admin']);
            $user = User::factory()->create(['role_label' => 'user']);

            expect(User::isAdmin($admin))->toBeTrue();
            expect(User::isAdmin($user))->toBeFalse();
        });
    });
});
