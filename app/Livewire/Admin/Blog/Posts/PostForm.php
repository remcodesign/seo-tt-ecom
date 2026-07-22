<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog\Posts;

use App\Models\Blog\Post;
use Livewire\Form as LivewireForm;

class PostForm extends LivewireForm
{
    public ?Post $post = null;

    public string $title = '';

    public string $slug = '';

    public int $user_id = 0;

    /**
     * @var array<int>
     */
    public array $category_ids = [];

    public ?string $body = '';

    public ?string $published_on = null;

    public function setPost(Post $post): void
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->user_id = $post->user_id;

        /** @var array<int> $rawCategoryIds */
        $rawCategoryIds = $post->categories->pluck('id')->all();
        $this->category_ids = $rawCategoryIds;

        $this->body = $post->body;
        $this->published_on = $post->published_on?->toDateString();
    }

    /**
     * Convert the form data to a DTO payload array.
     *
     * @return array<string, mixed>
     */
    public function toDtoPayload(): array
    {
        return [
            'user_id' => $this->user_id,
            'category_ids' => $this->category_ids,
            'title' => $this->title,
            'body' => $this->body,
            'published_on' => filled($this->published_on) ? $this->published_on : null,
        ];
    }
}
