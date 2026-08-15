<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use App\Data\HubSpot\Requests\AdminWarehouseRecommendationData;
use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Enums\RoleLabel;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Models\HubSpot\WarehouseRecommendationTask;
use App\Models\User;
use App\Services\HubSpot\Warehouse\AdminWarehouseRecommendationTaskService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class WarehouseWorkflow extends Component
{
    public WarehouseWorkflowForm $form;

    public ?string $taskId = null;

    /** @var array<string, mixed>|null */
    public ?array $taskResult = null;

    public string $errorMessage = '';

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user || $user->role_label !== RoleLabel::admin) {
            $this->redirectRoute('admin.login');

            return;
        }

        /** @var array<int|string, array{enabled?: bool, tenant_id?: string}> $portalTenants */
        $portalTenants = config('hubspot.portal_tenants', []);
        $firstPortal = array_key_first($portalTenants);

        if ($firstPortal !== null && ($portalTenants[$firstPortal]['enabled'] ?? false) === true) {
            $this->form->portal_id = (string) $firstPortal;
            $this->form->deal_id = '';
            $this->form->note_deal_id = '';
        }
    }

    public function runSynchronously(AdminWarehouseRecommendationTaskService $adminWarehouseRecommendationTaskService): void
    {
        $this->prepareRun();
        $warehouseRecommendationTask = $this->createTask($adminWarehouseRecommendationTaskService);

        if ($warehouseRecommendationTask->status !== WarehouseRecommendationTaskStatus::accepted) {
            $this->loadTask($warehouseRecommendationTask->id);

            return;
        }

        app(ProcessWarehouseRecommendation::class, ['taskId' => $warehouseRecommendationTask->id])->handle();
        $this->loadTask($warehouseRecommendationTask->id);
    }

    public function queueAsynchronously(AdminWarehouseRecommendationTaskService $adminWarehouseRecommendationTaskService): void
    {
        $this->prepareRun();
        $warehouseRecommendationTask = $this->createTask($adminWarehouseRecommendationTaskService);

        if ($warehouseRecommendationTask->status === WarehouseRecommendationTaskStatus::accepted) {
            ProcessWarehouseRecommendation::dispatch($warehouseRecommendationTask->id);
        }

        $this->loadTask($warehouseRecommendationTask->id);
    }

    public function refreshStatus(): void
    {
        if ($this->taskId !== null) {
            $this->loadTask($this->taskId);
        }
    }

    public function clearResults(): void
    {
        $this->taskId = null;
        $this->taskResult = null;
        $this->errorMessage = '';
    }

    /** @return array<string, array{tenant_id: string, label: string}> */
    public function portalOptions(): array
    {
        /** @var array<int|string, array{enabled?: bool, tenant_id?: string}> $portals */
        $portals = config('hubspot.portal_tenants', []);

        $options = [];
        foreach ($portals as $portalId => $portal) {
            if (($portal['enabled'] ?? false) !== true) {
                continue;
            }

            if (! is_string($portal['tenant_id'] ?? null)) {
                continue;
            }

            $options[(string) $portalId] = [
                'tenant_id' => $portal['tenant_id'],
                'label'     => $portalId.' / '.$portal['tenant_id'],
            ];
        }

        return $options;
    }

    private function prepareRun(): void
    {
        if ($this->form->note_deal_id === '') {
            $this->form->note_deal_id = $this->form->deal_id;
        }

        $this->validateForm();
        $this->errorMessage = '';
        $this->taskResult = null;
    }

    private function validateForm(): void
    {
        $rules = $this->form->rules();
        $rules['portal_id'][] = Rule::in(array_keys($this->portalOptions()));

        $this->form->validate($rules);
    }

    private function createTask(AdminWarehouseRecommendationTaskService $adminWarehouseRecommendationTaskService): WarehouseRecommendationTask
    {
        $portal = $this->portalOptions()[$this->form->portal_id];
        $callbackId = $this->form->callback_id !== '' ? $this->form->callback_id : 'admin-test-'.Str::ulid()->toBase32();
        $noteDealId = $this->form->note_deal_id;

        $warehouseRecommendationTask = $adminWarehouseRecommendationTaskService->create(new AdminWarehouseRecommendationData(
            portal_id: $this->form->portal_id,
            tenant_id: $portal['tenant_id'],
            deal_id: $this->form->deal_id,
            note_deal_id: $noteDealId,
            callback_id: $callbackId,
            workflow_id: $this->form->workflow_id !== '' ? $this->form->workflow_id : null,
            action_definition_version: $this->form->action_definition_version,
        ));

        $this->taskId = $warehouseRecommendationTask->id;
        $this->form->callback_id = $callbackId;

        Log::channel('hubspot')->info('Admin warehouse workflow requested.', [
            'event'      => 'admin.workflow_requested',
            'task_id'    => $warehouseRecommendationTask->id,
            'admin_id'   => auth()->id(),
            'execution'  => 'admin_console',
            'tenant_id'  => $warehouseRecommendationTask->tenant_id,
            'deal_id'    => $warehouseRecommendationTask->deal_id,
            'task_state' => $warehouseRecommendationTask->status->value,
        ]);

        return $warehouseRecommendationTask;
    }

    private function loadTask(string $taskId): void
    {
        $task = WarehouseRecommendationTask::query()->find($taskId);
        if (! $task instanceof WarehouseRecommendationTask) {
            $this->errorMessage = 'The workflow task could not be found.';

            return;
        }

        $this->taskId = $task->id;
        $this->taskResult = [
            'id'               => $task->id,
            'status'           => $task->status->value,
            'portal_id'        => $task->portal_id,
            'tenant_id'        => $task->tenant_id,
            'deal_id'          => $task->deal_id,
            'source'           => $task->source,
            'callback_sent_at' => $task->callback_sent_at?->toIso8601String(),
            'started_at'       => $task->started_at?->toIso8601String(),
            'completed_at'     => $task->completed_at?->toIso8601String(),
            'failure_code'     => $task->failure_code,
            'note_id'          => $task->note_id,
            'result'           => $task->result,
            'debug_trace'      => $task->debug_trace ?? [],
        ];

        Log::channel('hubspot')->debug('Admin warehouse workflow displayed.', [
            'event'        => 'admin.workflow_displayed',
            'task_id'      => $task->id,
            'status'       => $task->status->value,
            'result_items' => is_array($task->result['items'] ?? null) ? count($task->result['items']) : 0,
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.hubspot.warehouse-workflow', [
            'portalOptions' => $this->portalOptions(),
        ]);
    }
}
