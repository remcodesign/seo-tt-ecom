<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Blog\Requests\StoreCommentData;
use App\Data\Blog\Requests\StorePostData;
use App\Enums\RoleLabel;
use App\Models\Blog\Post;
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
                'is_admin' => false,
                'role_label' => RoleLabel::writer,
            ]);

        $commenters = User::factory()
            ->count(5)
            ->create([
                'is_admin' => false,
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

        // Create published posts with comments
        $publishedPosts = collect($publishedTitles)->map(function (string $title, int $index) use ($writers, $postService, $generator, $makeBody): Post {
            $writer = $writers->get($index % $writers->count());
            assert($writer instanceof User);

            return $postService->create(
                $writer,
                new StorePostData(
                    title: $title,
                    body: $makeBody($generator),
                    published_on: Carbon::now()->subDays(7 * ($index + 1))->toDateTimeString(),
                ),
            );
        });

        // Create comments for each published post
        foreach ($publishedPosts as $index => $post) {
            $commentCount = $commentCounts[$index] ?? 2;

            for ($commentIndex = 0; $commentIndex < $commentCount; $commentIndex++) {
                $commenter = $commenters->get(($index + $commentIndex) % $commenters->count());
                assert($commenter instanceof User);

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

        $writer = $writers->get(1);
        assert($writer instanceof User);

        // Create a draft post with a draft-only comment.
        $draftPost = $postService->create(
            $writer,
            new StorePostData(
                title: 'Drafting the Next Big Feature',
                body: $makeBody($generator),
            ),
        );

        $draftCommenter = $commenters->first();
        assert($draftCommenter instanceof User);

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
