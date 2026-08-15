<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Enums\RoleLabel;
use App\Models\HubSpot\WarehouseRecommendationTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.layouts.admin')]
class WarehouseLogs extends Component
{
    use WithPagination;

    public WarehouseWorkflowLogForm $form;

    public ?string $selectedTaskId = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user || $user->role_label !== RoleLabel::admin) {
            $this->redirectRoute('admin.login');
        }
    }

    public function selectTask(string $taskId): void
    {
        $this->selectedTaskId = WarehouseRecommendationTask::query()
            ->whereKey($taskId)
            ->exists() ? $taskId : null;
    }

    public function clearSelection(): void
    {
        $this->selectedTaskId = null;
    }

    public function resetFilters(): void
    {
        $this->form->reset();
        $this->form->status = 'all';
        $this->form->source = 'all';
        $this->form->date_filter = 'all';
        $this->resetPage();
    }

    /** @return array<string, string> */
    public function dateFilters(): array
    {
        return [
            'all'          => 'All dates',
            'today'        => 'Today',
            'yesterday'    => 'Yesterday',
            'this_week'    => 'This week',
            'this_month'   => 'This month',
            'last_7_days'  => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
        ];
    }

    /** @return array<string, string> */
    public function statuses(): array
    {
        return ['all' => 'All statuses'] + collect(WarehouseRecommendationTaskStatus::cases())
            ->mapWithKeys(fn (WarehouseRecommendationTaskStatus $warehouseRecommendationTaskStatus): array => [$warehouseRecommendationTaskStatus->value => str($warehouseRecommendationTaskStatus->value)->headline()->toString()])
            ->all();
    }

    /** @return array<string, string> */
    public function sources(): array
    {
        return ['all' => 'All sources'] + WarehouseRecommendationTask::query()
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source', 'source')
            ->mapWithKeys(function (mixed $source): array {
                $str = is_string($source) ? $source : '';

                return [$str => $str];
            })
            ->all();
    }

    public function updatedFormSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFormStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFormSource(): void
    {
        $this->resetPage();
    }

    public function updatedFormDateFilter(): void
    {
        $this->resetPage();
    }

    /** @return Builder<WarehouseRecommendationTask> */
    private function filteredTasks(): Builder
    {
        return WarehouseRecommendationTask::query()
            ->when($this->form->search !== '', function (Builder $builder): void {
                $search = '%'.$this->form->search.'%';
                $builder->where(function (Builder $builder) use ($search): void {
                    $builder->where('id', 'like', $search)
                        ->orWhere('portal_id', 'like', $search)
                        ->orWhere('tenant_id', 'like', $search)
                        ->orWhere('deal_id', 'like', $search)
                        ->orWhere('callback_id', 'like', $search)
                        ->orWhere('workflow_id', 'like', $search)
                        ->orWhere('failure_code', 'like', $search);
                });
            })
            ->when($this->form->status !== 'all', fn (Builder $builder): Builder => $builder->where('status', $this->form->status))
            ->when($this->form->source !== 'all', fn (Builder $builder): Builder => $builder->where('source', $this->form->source))
            ->when($this->form->date_filter !== 'all', fn (Builder $builder): Builder => $builder->where('created_at', '>=', $this->dateFilterStart()))
            ->latest();
    }

    private function dateFilterStart(): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match ($this->form->date_filter) {
            'today'        => $now->startOfDay(),
            'yesterday'    => $now->subDay()->startOfDay(),
            'this_week'    => $now->startOfWeek(),
            'this_month'   => $now->startOfMonth(),
            'last_7_days'  => $now->subDays(7),
            'last_30_days' => $now->subDays(30),
            default        => $now->subYears(100),
        };
    }

    /** @return array{total: int, accepted: int, processing: int, succeeded: int, failed: int, expired: int} */
    private function summary(): array
    {
        $counts = $this->filteredTasks()
            ->reorder()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => match (true) {
                is_numeric($count) => (int) $count,
                default            => 0,
            });
        $total = 0;

        foreach ($counts as $count) {
            $total += $count;
        }

        return [
            'total'      => $total,
            'accepted'   => $counts->get(WarehouseRecommendationTaskStatus::accepted->value, 0),
            'processing' => $counts->get(WarehouseRecommendationTaskStatus::processing->value, 0),
            'succeeded'  => $counts->get(WarehouseRecommendationTaskStatus::succeeded->value, 0),
            'failed'     => $counts->get(WarehouseRecommendationTaskStatus::failed->value, 0),
            'expired'    => $counts->get(WarehouseRecommendationTaskStatus::expired->value, 0),
        ];
    }

    public function render(): View
    {
        $selectedTask = $this->selectedTaskId === null
            ? null
            : WarehouseRecommendationTask::query()->find($this->selectedTaskId);

        return view('livewire.admin.hubspot.warehouse-logs', [
            'tasks'        => $this->filteredTasks()->paginate(15),
            'selectedTask' => $selectedTask,
            'summary'      => $this->summary(),
        ]);
    }
}
