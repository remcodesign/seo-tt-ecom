<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'body' => fake()->paragraphs(3, true),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'published_on' => fake()->dateTimeBetween('-1 years', 'now'),
        ];
    }
}
