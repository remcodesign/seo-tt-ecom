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

    public ?string $errorMessage = null;

    public function mount(Post $post): void
    {
        $this->post = $post;
    }

    public function delete(int $commentId): void
    {
        if (! $this->post->exists) {
            $this->errorMessage = 'Cannot delete comment: Post does not exist.';

            return;
        }

        $comment = $this->post->comments()->find($commentId);

        if (! $comment) {
            $this->errorMessage = 'Cannot delete comment: Comment does not exist.';

            return;
        }

        $comment->delete();
        $this->errorMessage = null;
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
