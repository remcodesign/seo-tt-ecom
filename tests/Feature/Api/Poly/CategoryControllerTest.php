<?php

declare(strict_types=1);

use App\Models\Blog\Post;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('CategoryController (API)', function (): void {
    describe('index', function (): void {
        it('returns paginated categories filtered by type', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $category = Category::factory()->create();
            $post = Post::factory()->for($user)->create();
            $post->categories()->attach($category);

            $response = $this->getJson('/api/poly/categories?type=blog_post');

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'slug'],
                    ],
                    'meta',
                    'links',
                ])
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $category->id);
        });

        it('excludes categories not linked to the given type', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $linkedCategory = Category::factory()->create();
            $unlinkedCategory = Category::factory()->create();

            $post = Post::factory()->for($user)->create();
            $post->categories()->attach($linkedCategory);

            $response = $this->getJson('/api/poly/categories?type=blog_post');

            $response->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $linkedCategory->id);
        });

        it('returns 422 when type parameter is missing', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $this->getJson('/api/poly/categories')
                ->assertStatus(422);
        });

        it('returns 422 when type parameter is invalid', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $this->getJson('/api/poly/categories?type=invalid')
                ->assertStatus(422);
        });

        it('respects the per_page query parameter', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $categories = Category::factory()->count(5)->create();
            foreach ($categories as $category) {
                $post = Post::factory()->for($user)->create();
                $post->categories()->attach($category);
            }

            $response = $this->getJson('/api/poly/categories?type=blog_post&per_page=3');

            $response->assertSuccessful()
                ->assertJsonPath('meta.per_page', 3)
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('meta.total', 5);
        });

        it('clamps per_page to the maximum allowed value', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $categories = Category::factory()->count(30)->create();
            foreach ($categories as $category) {
                $post = Post::factory()->for($user)->create();
                $post->categories()->attach($category);
            }

            $response = $this->getJson('/api/poly/categories?type=blog_post&per_page=999');

            $response->assertSuccessful()
                ->assertJsonPath('meta.per_page', 100)
                ->assertJsonCount(30, 'data');
        });

        it('clamps per_page to a minimum of 1', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $category = Category::factory()->create();
            $post = Post::factory()->for($user)->create();
            $post->categories()->attach($category);

            $this->getJson('/api/poly/categories?type=blog_post&per_page=0')
                ->assertSuccessful()
                ->assertJsonPath('meta.per_page', 1);
        });

        it('orders by name ascending by default', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $categoryB = Category::factory()->create(['name' => 'Beta']);
            $categoryA = Category::factory()->create(['name' => 'Alpha']);

            foreach ([$categoryA, $categoryB] as $category) {
                $post = Post::factory()->for($user)->create();
                $post->categories()->attach($category);
            }

            $response = $this->getJson('/api/poly/categories?type=blog_post');

            $response->assertSuccessful();

            $names = collect($response->json('data'))->pluck('name')->all();
            expect($names)->toBe(['Alpha', 'Beta']);
        });

        it('orders by name desc when orderby has _desc suffix', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $categoryB = Category::factory()->create(['name' => 'Beta']);
            $categoryA = Category::factory()->create(['name' => 'Alpha']);

            foreach ([$categoryA, $categoryB] as $category) {
                $post = Post::factory()->for($user)->create();
                $post->categories()->attach($category);
            }

            $response = $this->getJson('/api/poly/categories?type=blog_post&orderby=name_desc');

            $response->assertSuccessful();

            $names = collect($response->json('data'))->pluck('name')->all();
            expect($names)->toBe(['Beta', 'Alpha']);
        });

        it('falls back to default ordering for an invalid orderby value', function (): void {
            Sanctum::actingAs(User::factory()->create());

            $user = User::factory()->create();
            $category = Category::factory()->create();
            $post = Post::factory()->for($user)->create();
            $post->categories()->attach($category);

            $this->getJson('/api/poly/categories?type=blog_post&orderby=invalid_column')
                ->assertSuccessful()
                ->assertJsonPath('meta.total', 1);
        });

        it('rejects unauthenticated requests', function (): void {
            $this->getJson('/api/poly/categories?type=blog_post')
                ->assertUnauthorized();
        });
    });
});
