<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog\Posts;

use App\Data\Blog\Requests\StorePostData;
use App\Data\Blog\Requests\UpdatePostData;
use App\Models\Blog\Post;
use App\Models\Category;
use App\Models\User;
use App\Services\Blog\PostService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Form extends Component
{
    public PostForm $form;

    public function mount(?Post $post = null): void
    {
        if ($post instanceof Post && $post->exists) {
            $post->loadMissing('categories');
            $this->form->setPost($post);
        }
    }

    public function save(PostService $postService): void
    {
        // Update of an existing post
        if ($this->form->post?->exists) {
            $postService->update(
                $this->form->post,
                UpdatePostData::validateAndCreate($this->form->toDtoPayload())
            );

            session()->flash('status', 'Post updated successfully.');
            $this->redirectRoute('admin.blog.posts.index');

            return;
        }

        // Create a new post
        $postService->create(
            StorePostData::validateAndCreate($this->form->toDtoPayload())
        );

        session()->flash('status', 'Post created successfully.');
        $this->redirectRoute('admin.blog.posts.index');
    }

    // todo add images via native laravel `First-Party Image Processing`
    // https://laravel-news.com/laravel-13-20-0?utm_source=newsletter&utm_medium=email&utm_campaign=624&utm_content=weekly&bento_uuid=61000f95-fc3e-493e-a055-e04be3ca9074
    public function render(): View
    {
        return view('livewire.admin.blog.posts.form', [
            'writers' => User::getWriters(),
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id')->toArray(),
        ]);
    }
}
