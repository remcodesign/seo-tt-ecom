<?php

declare(strict_types=1);

namespace App\Livewire\Components\Blog\Posts;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class FilterSearch extends Component
{
    public string $search = '';

    public function mount(): void {}

    public function updatedSearch(): void
    {
        $this->dispatch('searchUpdated', $this->search);
    }

    public function render(): View
    {
        return view('livewire.components.blog.posts.filter-search');
    }
}
