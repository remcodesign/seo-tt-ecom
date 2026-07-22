<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\StorePostData;
use App\Enums\RoleLabel;
use App\Models\Blog\Post;
use App\Models\Category;
use App\Models\User;
use App\Services\Blog\CommentService;
use App\Services\Blog\PostService;
use Faker\Generator as Faker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class BlogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $generator = app(Faker::class);

        $makeBody = static function (Faker $faker): string {
            /** @var array<int, string> $paragraphs */
            $paragraphs = $faker->paragraphs(4, false);

            return implode("\n\n", $paragraphs);
        };

        // Create users for writers and commenters
        $writers = User::factory()
            ->count(2)
            ->create([
                'role_label' => RoleLabel::writer,
            ]);

        $commenters = User::factory()
            ->count(5)
            ->create([
                'role_label' => RoleLabel::user,
            ]);

        // Use the real services to create posts and comments, so that the slug generation and other logic is exercised
        $postService = app(PostService::class);
        $commentService = app(CommentService::class);

        $publishedTitles = [
            'The Anatomy of a Modern Laravel Blog',
            'The Anatomy of a Modern Laravel Blog',
            'Writing Maintainable Services in Laravel',
            'How to Seed Test Content the Right Way',
            'Publishing with Scheduled Post Dates',
            'Why Immutable Dates Make Your App Safer',
            'Testing Public Content Without Authentication',
        ];

        $commentCounts = [1, 2, 3, 4, 1, 3, 2];

        // Fetch the "Uncategorized" category (seeded by CategorySeeder) and add more categories
        $categoriesList = [];
        // 'uncategorized' should be guaranteed to exist via the CategorySeeder,
        // failing if it does not exist. This is a PHPStan guarantee, so that the seeder can be run in any order without worrying about the "Uncategorized" category not existing.
        $categoriesList[] = Category::query()->where('slug', 'uncategorized')->firstOrFail();
        $categoriesList[] = Category::factory()->create(['name' => 'DB Migrations', 'slug' => 'db-migrations']);
        $categoriesList[] = Category::factory()->create(['name' => 'Laravel Testing', 'slug' => 'laravel-testing']);
        $categoriesList[] = Category::factory()->create(['name' => 'Frontend Development', 'slug' => 'frontend-development']);

        // Create published posts with comments
        $publishedPosts = collect($publishedTitles)->map(function (string $title, int $index) use ($writers, $postService, $generator, $makeBody, $categoriesList): Post {
            /** @var User $writer */
            $writer = $writers->get($index % $writers->count());

            // choose 2 random categories or "Uncategorized" from the list of categories
            $randChoice = random_int(0, 1);
            if ($randChoice === 0) {
                do {
                    $categoriesRaw = array_rand($categoriesList, 2);
                    /** @var Category[] $categories */
                    $categories = [$categoriesList[$categoriesRaw[0]], $categoriesList[$categoriesRaw[1]]];
                } while (
                    collect($categories)->pluck('name')->contains('[Uncategorized]')
                );
            } else {
                /** @var Category[] $categories */
                $categories = [$categoriesList[0]]; // "Uncategorized" category
            }

            // categories has many rows, convert to array of IDs for the DTO `category_ids`
            /** @var array<int> $categoryIds */
            $categoryIds = collect($categories)->pluck('id')->all();

            return $postService->create(
                new StorePostData(
                    user_id: $writer->id,
                    title: $title,
                    category_ids: $categoryIds,
                    body: $makeBody($generator),
                    published_on: Carbon::now()->subDays(7 * ($index + 1))->toImmutable(),
                ),
            );
        });

        // Create comments for each published post
        foreach ($publishedPosts as $index => $post) {
            $commentCount = $commentCounts[$index] ?? 2;

            for ($commentIndex = 0; $commentIndex < $commentCount; $commentIndex++) {
                /** @var User $commenter */
                $commenter = $commenters->get(($index + $commentIndex) % $commenters->count());

                $commentService->create(
                    $commenter,
                    $post,
                    new StoreCommentData(
                        post_id: $post->id,
                        comment: $generator->paragraph(),
                    ),
                );
            }
        }

        /** @var User $writer */
        $writer = $writers->get(1);

        // Create a draft post with a draft-only comment.
        /** @var Category $draftCategory */
        $draftCategory = $categoriesList[array_rand($categoriesList, 1)];

        $draftPost = $postService->create(
            new StorePostData(
                user_id: $writer->id,
                title: 'Drafting the Next Big Feature',
                category_ids: [$draftCategory->id],
                body: $makeBody($generator),
            ),
        );

        /** @var User $draftCommenter */
        $draftCommenter = $commenters->first();

        // Create a comment for the draft post, which should not be visible in the public API.
        $commentService->create(
            $draftCommenter,
            $draftPost,
            new StoreCommentData(
                post_id: $draftPost->id,
                comment: $generator->paragraph(),
            ),
        );
    }
}
