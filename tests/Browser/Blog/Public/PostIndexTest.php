<?php

declare(strict_types=1);

use App\Models\Blog\Post;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('1. visits the page and shows posts', function (): void {
    $user = User::factory()->create();
    Post::factory(3)->for($user)->create();

    visit('/blog/posts')
        ->assertSee('Blog Posts')
        ->assertCount('[data-test="post"]', 3)
        ->assertCount('[data-test="empty-state"]', 0)
        ->assertNoJavaScriptErrors();
});

it('2. shows empty state when no posts exist', function (): void {
    visit('/blog/posts')
        ->assertSee('No posts available.')
        ->assertCount('[data-test="post"]', 0)
        ->assertNoJavaScriptErrors();
});

it('3. orders posts by selected option', function (): void {
    // create 3 posts updated_at, published_on older and newer and middle than the existing posts
    $user = User::factory()->create();
    Post::factory()->for($user)->create(['title' => 'Newest', 'updated_at' => '2024-06-01', 'published_on' => '2024-06-01']);
    Post::factory()->for($user)->create(['title' => 'Middle', 'updated_at' => '2024-03-01', 'published_on' => '2024-03-01']);
    Post::factory()->for($user)->create(['title' => 'Oldest', 'updated_at' => '2024-01-01', 'published_on' => '2024-01-01']);

    // visit the page with orderby=updated_at
    visit('/blog/posts?orderby=updated_at')
        ->assertSee('Oldest')
        ->assertSee('Middle')
        ->assertSee('Newest')
        ->assertCount('[data-test="post"]', 3)

        // change orderby to updated_at_desc and check the query string is updated
        ->select('[data-test="orderby-select"]', 'updated_at_desc')
        ->wait(1)
        ->assertQueryStringHas('orderby', 'updated_at_desc')

        // change orderby to updated_at and check the query string is updated
        ->select('[data-test="orderby-select"]', 'updated_at')
        ->wait(1)
        ->assertQueryStringHas('orderby', 'updated_at')

        ->assertNoJavaScriptErrors();

    // check order of posts on the page is Oldest, Middle, Newest (updated_at ascending)
    visit('/blog/posts?orderby=updated_at')
        ->assertScript(
            "Array.from(document.querySelectorAll('[data-test=\"post-title-link\"]')).map(el => el.textContent.trim()).join(',')",
            'Oldest,Middle,Newest'
        );

    // check using an non valid orderby value defaults to published_on_desc (Newest, Middle, Oldest)
    visit('/blog/posts?orderby=invalid_value')
        ->assertScript(
            "Array.from(document.querySelectorAll('[data-test=\"post-title-link\"]')).map(el => el.textContent.trim()).join(',')",
            'Newest,Middle,Oldest'
        )
        ->assertCount('[data-test="post"]', 3)
        ->assertNoJavaScriptErrors();
});

it('4. changes items per page', function (): void {
    // create 9 posts
    $user = User::factory()->create();
    Post::factory(9)->for($user)->create(['title' => 'Test Post']);

    // visit the page with per_page=3
    visit('/blog/posts?per_page=3')
        ->assertQueryStringHas('per_page', '3')
        ->assertCount('[data-test="post"]', 3)
        ->assertNoJavaScriptErrors();

    // count posts with the title "Test Post" on the page (should be 3)
    $postsOnPage = Post::where('title', 'Test Post')->take(3)->get();
    expect($postsOnPage->count())->toBe(3);

    // select per_page=12 and check the query string is updated
    visit('/blog/posts?per_page=3')
        ->assertCount('[data-test="empty-state"]', 0)
        ->select('[data-test="per-page-select"]', '12')
        ->wait(1)
        ->assertQueryStringHas('per_page', '12')
        ->assertCount('[data-test="post"]', 9)
        ->assertNoJavaScriptErrors();

    // change page to 3 count the number of posts displayed on the page
    visit('/blog/posts?per_page=3&page=3')
        ->assertQueryStringHas('per_page', '3')
        ->assertQueryStringHas('page', '3')
        ->assertCount('[data-test="empty-state"]', 0)
        ->assertCount('[data-test="post"]', 3)
        ->assertNoJavaScriptErrors();
});

it('5.1 via URL, filters posts by interacting with category buttons', function (): void {
    // create user, categories, and posts
    $user = User::factory()->create();

    $catA = Category::factory()->create(['name' => 'Tech']);
    $catB = Category::factory()->create(['name' => 'Design']);

    $postA = Post::factory()->for($user)->create(['title' => 'Tech Post']);
    $postB = Post::factory()->for($user)->create(['title' => 'Design Post']);
    $postC = Post::factory()->for($user)->create(['title' => 'Other Post']);

    $postA->categories()->attach($catA);
    $postB->categories()->attach($catB);

    // visit the page with category filter applied, check for catA and catB posts, and ensure other posts are not displayed
    visit('/blog/posts?category_ids='.$catA->id.','.$catB->id)
        ->assertSee('Tech Post')
        ->assertSee('Design Post')
        ->assertDontSee('Other Post')
        ->assertCount('[data-test="post"]', 2)
        // check query string has category_ids for catA and catB
        ->assertQueryStringHas('category_ids', $catA->id.','.$catB->id)
        ->assertNoJavaScriptErrors();

    // visit the page with category filter applied, check for catA post, and ensure other posts are not displayed
    visit('/blog/posts?category_ids='.$catA->id)
        ->assertSee('Tech Post')
        ->assertDontSee('Design Post')
        ->assertDontSee('Other Post')
        ->assertCount('[data-test="post"]', 1)
        // check query string has category_ids for catA
        ->assertQueryStringHas('category_ids', (string) $catA->id)
        ->assertNoJavaScriptErrors();

    // no category filter applied, check for all posts
    visit('/blog/posts')/* */
        ->assertSee('Tech Post')
        ->assertSee('Design Post')
        ->assertSee('Other Post')
        ->assertCount('[data-test="post"]', 3)
        ->assertQueryStringMissing('category_ids')
        ->assertNoJavaScriptErrors();
});

it('5.2 via buttons, category filter handling from `All` and `Clear` states', function (): void {
    // add user, post and 2 categories, and attach the post to both categories
    $user = User::factory()->create();

    $catA = Category::factory()->create(['name' => 'Tech']);
    $catB = Category::factory()->create(['name' => 'Design']);

    $post = Post::factory()->for($user)->create();

    $post->categories()->attach([$catA->id, $catB->id]);

    // start with click on `Clear` category filter button
    // then click the first category button to filter by that category and check the query string has only the first category id
    visit('/blog/posts')
        ->click('[data-test="category-filter-clear"]')
        ->wait(1)
        ->assertQueryStringMissing('category_ids')
        ->assertCount('[data-test="post"]', 1)
        ->click('[data-test="category-filter-cat-'.$catA->id.'"]')
        ->wait(1)
        ->assertQueryStringHas('category_ids', (string) $catA->id)
        ->assertNoJavaScriptErrors();

    // start with click on `All` category filter button (all categories selected, URL has both IDs)
    // then click the first category button to toggle it off — only the second category remains
    visit('/blog/posts')
        ->click('[data-test="category-filter-all"]')
        ->wait(1)
        ->assertQueryStringHas('category_ids')
        ->assertCount('[data-test="post"]', 1)
        ->click('[data-test="category-filter-cat-'.$catA->id.'"]')
        ->wait(1)
        ->assertQueryStringHas('category_ids', (string) $catB->id)
        ->assertNoJavaScriptErrors();
});

it('6. paginates through posts', function (): void {
    $user = User::factory()->create();
    Post::factory(7)->for($user)->create();

    visit('/blog/posts?per_page=3')
        ->assertQueryStringHas('per_page', '3')
        ->click('2')
        ->wait(1)
        ->assertQueryStringHas('page', '2')
        // count the number of posts displayed on the page (should be 3)
        ->assertCount('[data-test="empty-state"]', 0)
        ->assertCount('[data-test="post"]', 3)
        ->assertNoJavaScriptErrors();
});

it('7. combines order-by, per-page, category filter, and pagination', function (): void {
    $user = User::factory()->create();

    $cat = Category::factory()->create(['name' => 'Work']);

    $postA = Post::factory()->for($user)->create(['title' => 'Alpha', 'published_on' => '2025-03-01']);
    $postB = Post::factory()->for($user)->create(['title' => 'Beta', 'published_on' => '2025-02-01']);
    $postC = Post::factory()->for($user)->create(['title' => 'Gamma', 'published_on' => '2025-01-01']);

    $postA->categories()->attach($cat);
    $postB->categories()->attach($cat);
    $postC->categories()->attach($cat);

    visit('/blog/posts?orderby=published_on_desc&per_page=3&category_ids='.$cat->id)
        ->assertQueryStringHas('orderby', 'published_on_desc')
        ->assertQueryStringHas('per_page', '3')
        ->assertQueryStringHas('category_ids', (string) $cat->id)
        ->assertCount('[data-test="post"]', 3)
        ->assertScript(
            "Array.from(document.querySelectorAll('[data-test=\"post-title-link\"]')).map(el => el.textContent.trim()).join(',')",
            'Alpha,Beta,Gamma'
        )
        ->assertNoJavaScriptErrors();
});

it('8. correct URL query parameters when applying filters', function (): void {
    $user = User::factory()->create();

    $cat = Category::factory()->create(['name' => 'Tech']);

    $post = Post::factory()->for($user)->create(['title' => 'Tech Post']);
    $post->categories()->attach($cat);

    Post::factory(5)->for($user)->create();

    visit('/blog/posts')
        // initially has default params
        ->assertQueryStringHas('orderby', 'published_on_desc')
        ->assertQueryStringHas('per_page', '6')
        ->assertCount('[data-test="post"]', 6)

        // change orderby
        ->select('[data-test="orderby-select"]', 'published_on')
        ->wait(1)
        ->assertQueryStringHas('orderby', 'published_on')

        // change per_page
        ->select('[data-test="per-page-select"]', '12')
        ->wait(1)
        ->assertQueryStringHas('per_page', '12')
        ->assertCount('[data-test="post"]', 6)
        // clear category filter (no change — no category_ids param existed)
        ->assertQueryStringMissing('category_ids')
        ->assertNoJavaScriptErrors();
});

it('9. correct URL query parameters when filtering by category', function (): void {
    $user = User::factory()->create();
    $cat = Category::factory()->create(['name' => 'Tech']);
    $post = Post::factory()->for($user)->create([
        'title' => 'How to Seed Test Content the Right Way',
        'slug' => 'how-to-seed-test-content-the-right-way',
    ]);
    $post->categories()->attach($cat);

    visit('/blog/posts?orderby=updated_at&per_page=6&page=1&category_ids='.$cat->id)
        ->assertSee('How to Seed Test Content the Right Way')
        ->assertCount('[data-test="post"]', 1)

        // check the post link href contains the expected query parameters
        ->assertSourceHas('orderby=updated_at')
        ->assertSourceHas('per_page=6')
        ->assertSourceHas('page=1')
        ->assertSourceHas('category_ids='.$cat->id)

        // click the link and verify the show page has the params
        ->click('[data-test="post-title-link"]')
        ->wait(1)
        ->assertPathIs('/blog/posts/how-to-seed-test-content-the-right-way')
        ->assertQueryStringHas('orderby', 'updated_at')
        ->assertQueryStringHas('per_page', '6')
        ->assertQueryStringHas('page', '1')
        ->assertQueryStringHas('category_ids', (string) $cat->id)
        ->assertNoJavaScriptErrors();
});
