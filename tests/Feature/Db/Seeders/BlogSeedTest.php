<?php

declare(strict_types=1);

use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Database\Seeders\BlogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BlogSeeder', function (): void {
    it('creates seven published posts and one draft post', function (): void {
        $this->seed(BlogSeeder::class);

        expect(Post::count())->toBe(8);
        expect(Post::whereNotNull('published_on')->count())->toBe(7);
        expect(Post::whereNull('published_on')->count())->toBe(1);
    });

    it('creates comments only for published posts', function (): void {
        $this->seed(BlogSeeder::class);

        $publishedPostIds = Post::whereNotNull('published_on')->pluck('id');
        $draftPostIds = Post::whereNull('published_on')->pluck('id');

        expect(Comment::whereIn('post_id', $publishedPostIds)->count())->toBeGreaterThan(0);
        expect(Comment::whereIn('post_id', $draftPostIds)->count())->toBe(0);
    });

    it('creates two writers and five commenters', function (): void {
        $this->seed(BlogSeeder::class);

        expect(User::count())->toBe(7);
        expect(User::whereHas('posts')->count())->toBe(2);
        expect(User::whereHas('comments')->count())->toBe(5);
        expect(User::where('role_label', 'writer')->count())->toBe(2);
        expect(User::where('role_label', 'user')->count())->toBe(5);
    });
});
