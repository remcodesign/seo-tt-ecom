<?php

declare(strict_types=1);

use App\Models\Blog\Post;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Category', function (): void {
    describe('Configuration & Data Integrity', function (): void {
        it('creates a category using the factory', function (): void {
            $category = Category::factory()->create();

            expect($category)->toBeInstanceOf(Category::class);
            expect($category->exists)->toBeTrue();
            expect($category->name)->toBeString();
            expect($category->slug)->toBeString();
            expect($category->toArray())->toHaveKey('name');
        });
    });

    describe('Relationship Integrity', function (): void {
        it('has many blog posts through categorizables', function (): void {
            $category = Category::factory()->create();
            $post = Post::factory()->create();

            $post->categories()->sync([$category->id]);

            expect($category->posts)->toHaveCount(1);
            expect($category->posts->first())->toBeInstanceOf(Post::class);
            expect($category->posts->first()?->id)->toBe($post->id);
        });
    });

    describe('Database Constraints', function (): void {
        it('prevents deletion of the "Uncategorized" category', function (): void {
            $uncategorized = Category::factory()->create(['slug' => 'uncategorized']);

            expect(fn () => $uncategorized->delete())
                ->toThrow(RuntimeException::class, 'The "[Uncategorized]" category is protected and cannot be deleted.');
        });

        it('allows deletion of other categories', function (): void {
            $category = Category::factory()->create();

            $category->delete();

            expect(Category::find($category->id))->toBeNull();
        });
    });
});
