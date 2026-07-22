<?php

declare(strict_types=1);

namespace App\Livewire\Components\Blog\Posts;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class FilterSearch extends Component
{
    public string $search = '';

    // string for 'all' category, int for specific category ID
    public int|string $category = 'all';

    /**
     * @var array<int, string>
     */
    public array $categories = [];

    /**
     * @param  array<int, string>  $categories
     */
    public function mount(array $categories = []): void
    {
        $this->categories = $categories;
    }

    public function updatedSearch(): void
    {
        $this->dispatch('searchUpdated', $this->search);
    }

    public function updatedCategory(): void
    {
        $this->dispatch('categoryFilterUpdated', $this->category);
    }

    public function render(): View
    {
        return view('livewire.components.blog.posts.filter-search');
    }
}
