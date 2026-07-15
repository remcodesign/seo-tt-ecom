<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
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

    public string $roleLabelFilter = 'all';

    public string $orderBy = 'id';

    public string $orderDirection = 'desc';

    public string $search = '';

    #[On('searchUpdated')]
    public function setSearch(string $search): void
    {
        $this->search = $search;
        $this->resetPage();
    }

    #[On('roleLabelFilterUpdated')]
    public function setRoleLabelFilter(string $roleLabelFilter): void
    {
        $this->roleLabelFilter = $roleLabelFilter;
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        $allowed = ['id', 'name', 'email'];

        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($this->orderBy === $column) {
            $this->orderDirection = $this->orderDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->orderBy = $column;
        $this->orderDirection = 'asc';
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === Auth::id()) {
            session()->flash('status', 'You cannot delete your own account.');

            return;
        }

        $user->delete();

        session()->flash('status', 'User deleted successfully.');
        $this->resetPage();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function roleLabels(): array
    {
        return collect(RoleLabel::cases())
        // "guest" Can not be assigned to users, so we exclude it from the filter options
            ->reject(static fn (RoleLabel $roleLabel): bool => $roleLabel->value === 'guest')
            ->map(static fn (RoleLabel $roleLabel): array => [
                'value' => $roleLabel->value,
                'label' => ucfirst($roleLabel->value),
            ])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.users.index', [
            'users' => User::query()
                ->when($this->roleLabelFilter !== 'all', fn ($query) => $query->where('role_label', $this->roleLabelFilter))
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->orderBy($this->orderBy, $this->orderDirection === 'asc' ? 'asc' : 'desc')
                ->paginate(5),
        ]);
    }
}
