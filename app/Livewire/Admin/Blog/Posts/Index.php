<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog\Posts;

use App\Models\Blog\Post;
use App\Services\Blog\PostService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.layouts.admin')]
/**
 * @property array<string, string> $listeners
 */
class Index extends Component
{
    use WithPagination;

    public string $orderBy = 'created_at';

    public string $orderDirection = 'desc';

    public string $search = '';

    /** @var string[] */
    public array $allowableOrderColumns = ['id', 'title', 'published_on', 'created_at'];

    #[On('searchUpdated')]
    public function setSearch(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, $this->allowableOrderColumns, true)) {
            return;
        }

        if ($this->orderBy === $column) {
            $this->orderDirection = $this->orderDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->orderBy = $column;
        $this->orderDirection = 'asc';
    }

    public function delete(int $postId, PostService $postService): void
    {
        $post = Post::findOrFail($postId);

        $postService->delete($post);

        session()->flash('status', 'Post deleted successfully.');
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.blog.posts.index', [
            'posts' => Post::query()
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('title', 'like', '%'.$this->search.'%');
                }))
                // with comment count to prevent N+1
                ->withCount('comments')
                ->orderBy($this->orderBy, $this->orderDirection === 'asc' ? 'asc' : 'desc')
                ->paginate(8),
        ]);
    }
}
