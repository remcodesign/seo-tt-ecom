<?php

declare(strict_types=1);

namespace App\Livewire\Components\Blog\Posts;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class FilterSearch extends Component
{
    public string $search = '';

    /**
     * @var array<int, array{value:string, label:string}>
     */
    public array $roleLabels = [];

    /**
     * @param  array<int, array{value:string, label:string}>  $roleLabels
     */
    public function mount(array $roleLabels = []): void
    {
        $this->roleLabels = $roleLabels;
    }

    public function updatedSearch(): void
    {
        $this->dispatch('searchUpdated', $this->search);
    }

    public function render(): View
    {
        return view('livewire.components.blog.posts.filter-search');
    }
}
