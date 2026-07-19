<?php

declare(strict_types=1);

namespace App\Livewire\Components\Blog\Posts;

use App\Models\Blog\Post;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CommentLister extends Component
{
    use WithPagination;

    public Post $post;

    public function mount(Post $post): void
    {
        $this->post = $post;
    }

    public function deleteComment(int $commentId): void
    {
        if (! $this->post->exists) {
            session()->flash('error', 'Cannot delete comment: Post does not exist.');

            return;
        }

        $this->post->comments()->findOrFail($commentId)->delete();
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.components.blog.posts.comment-lister', [
            'comments' => $this->post
                ->comments()
                ->orderBy('created_at', 'desc')
                ->paginate(3),
        ]);
    }
}
