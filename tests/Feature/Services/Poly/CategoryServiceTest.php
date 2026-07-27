<?php

declare(strict_types=1);

namespace App\Services\Poly {

    // No overrides needed for this service
}

namespace {

    use App\Models\Blog\Post;
    use App\Models\Category;
    use App\Models\User;
    use App\Services\Poly\CategoryService;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Pagination\LengthAwarePaginator;

    uses(RefreshDatabase::class);

    describe('CategoryService', function (): void {
        describe('query', function (): void {
            it('returns paginated categories filtered by categorizable type', function (): void {
                $user = User::factory()->create();
                $postCategory = Category::factory()->create();
                $unusedCategory = Category::factory()->create();

                $post = Post::factory()->for($user)->create();
                $post->categories()->attach($postCategory);

                $categoryService = app(CategoryService::class);

                $lengthAwarePaginator = $categoryService->query(categorizableType: Post::class);

                expect($lengthAwarePaginator)->toBeInstanceOf(LengthAwarePaginator::class)
                    ->and($lengthAwarePaginator->total())->toBe(1)
                    ->and($lengthAwarePaginator->first()->id)->toBe($postCategory->id);
            });

            it('excludes categories that have no categorizable records of the given type', function (): void {
                $user = User::factory()->create();
                $linkedCategory = Category::factory()->create();
                $unlinkedCategory = Category::factory()->create();

                // Attach the category to a post
                $post = Post::factory()->for($user)->create();
                $post->categories()->attach($linkedCategory);

                $categoryService = app(CategoryService::class);

                $lengthAwarePaginator = $categoryService->query(categorizableType: Post::class);

                expect($lengthAwarePaginator->total())->toBe(1);
            });
        });
    });
}
