<?php

declare(strict_types=1);

use App\Enums\PostWorkflowStatus;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\Blog\PostWorkflow;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

uses(RefreshDatabase::class);

describe('Post', function (): void {
    describe('Configuration & Data Integrity', function (): void {
        it('creates a post using the factory', function (): void {
            $post = Post::factory()->create();

            expect($post)->toBeInstanceOf(Post::class);
            expect($post->exists)->toBeTrue();
            expect($post->published_on)->toBeInstanceOf(CarbonImmutable::class);
            expect($post->body)->toBeString();
            expect($post->toArray())->toHaveKey('title');
        });

        it('allows the body to be null', function (): void {
            $post = Post::factory()->create(['body' => null]);

            expect($post->body)->toBeNull();
            expect($post->fresh()->body)->toBeNull();
        });

        it('allows published_on to be null', function (): void {
            $post = Post::factory()->create(['published_on' => null]);

            expect($post->published_on)->toBeNull();
            expect($post->fresh()->published_on)->toBeNull();
        });

        it('supports attaching media', function (): void {
            Storage::fake('public');
            $post = Post::factory()->create();

            $post->addMedia(UploadedFile::fake()->image('cover.jpg'))
                ->toMediaCollection();

            expect($post)->toBeInstanceOf(HasMedia::class);
            expect($post->getMedia())->toHaveCount(1);
            expect($post->getFirstMedia()->file_name)->toBe('cover.jpg');
        });

        it('has a workflow with a cast status and captured timestamp', function (): void {
            $post = Post::factory()->create();
            $workflow = PostWorkflow::create([
                'post_id' => $post->id,
                'file_hash' => sha1('seed-image'),
                'status' => PostWorkflowStatus::completed,
                'captured_at' => '2026-01-01 12:00:00',
                'embedding' => json_encode([0.0, 0.0]),
            ]);

            expect($post->workflow)->toBeInstanceOf(PostWorkflow::class);
            expect($workflow->post)->toBeInstanceOf(Post::class);
            expect($workflow->status)->toBe(PostWorkflowStatus::completed);
            expect($workflow->captured_at)->toBeInstanceOf(CarbonImmutable::class);
            expect($workflow->embedding)->toBe('[0,0]');
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
