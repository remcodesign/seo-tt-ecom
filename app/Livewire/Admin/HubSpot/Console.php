<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use App\Data\HubSpot\Requests\WarehouseRecommendationData;
use App\Enums\RoleLabel;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Models\User;
use App\Services\HubSpot\CustomerCheckService;
use App\Services\HubSpot\QuotePitchService;
use App\Services\HubSpot\Warehouse\WarehouseRecommendationService;
use App\Services\OpenRouter\OpenRouterService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Queue;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Console extends Component
{
    public string $email = 'vip@remcodesign.nl';

    public string $dealName = 'VIP Website Renewal';

    public string $dealAmount = '12000';

    public string $allowedDiscount = '15';

    public string $activeTab = 'quote';

    public string $sku = 'TV-55-OLED';

    public string $requestedQuantity = '2';

    public string $destinationPostalCode = '1012AB'; // not really used for the distance calculation, but just for testing

    /** @var array{is_vip: bool, lifetime_value: int, allowed_discount: int, reason: string, source: string}|null */
    public ?array $customerResult = null;

    /** @var array{text: string, provider: string, generated: bool, model: string|null}|null */
    public ?array $pitchResult = null;

    /** @var array<string, mixed>|null */
    public ?array $warehouseResult = null;

    public string $errorMessage = '';

    public string $workflowTaskId = '';

    /** @var array{status: string, task_id: string}|null */
    public ?array $workflowTaskResult = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user || $user->role_label !== RoleLabel::admin) {
            $this->redirectRoute('admin.login');
        }
    }

    public function checkCustomer(CustomerCheckService $customerCheckService): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $this->errorMessage = '';
        $this->pitchResult = null;
        $this->customerResult = $customerCheckService->check($this->email);
    }

    public function generatePitch(QuotePitchService $quotePitchService): void
    {
        $this->validate([
            'dealName'        => ['required', 'string', 'max:160'],
            'dealAmount'      => ['nullable', 'numeric', 'min:0'],
            'email'           => ['required', 'email', 'max:255'],
            'allowedDiscount' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->errorMessage = '';
        $this->pitchResult = $quotePitchService->generate(
            dealName: $this->dealName,
            dealAmount: $this->dealAmount === '' ? null : (float) $this->dealAmount,
            customerEmail: $this->email,
            allowedDiscount: (int) $this->allowedDiscount,
        );
    }

    public function recommendWarehouse(WarehouseRecommendationService $warehouseRecommendationService): void
    {
        $this->validate([
            'sku'                   => ['required', 'string', 'max:80'],
            'requestedQuantity'     => ['required', 'integer', 'min:1', 'max:1000'],
            'destinationPostalCode' => ['required', 'string', 'max:20'],
        ]);

        $this->errorMessage = '';
        $this->warehouseResult = $warehouseRecommendationService->recommend(
            new WarehouseRecommendationData(
                sku: $this->sku,
                requested_quantity: (int) $this->requestedQuantity,
                destination_postal_code: $this->destinationPostalCode,
            ),
        )->toArray();
    }

    public function clearResults(): void
    {
        $this->customerResult = null;
        $this->pitchResult = null;
        $this->warehouseResult = null;
        $this->errorMessage = '';
        $this->workflowTaskResult = null;
    }

    public function queueWarehouseTask(): void
    {
        $this->validate([
            'workflowTaskId' => ['required', 'string', 'exists:warehouse_recommendation_tasks,id'],
        ]);

        $this->errorMessage = '';
        Queue::push(new ProcessWarehouseRecommendation($this->workflowTaskId));
        $this->workflowTaskResult = [
            'status'  => 'queued',
            'task_id' => $this->workflowTaskId,
        ];
    }

    public function render(): View
    {
        $openRouterService = app(OpenRouterService::class);

        return view('livewire.admin.hubspot.console', [
            'aiConfigured' => $openRouterService->isConfigured(),
            'aiModel'      => config('ai.providers.openrouter.models.text.default'),
        ]);
    }
}
