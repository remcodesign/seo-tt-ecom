<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use App\Enums\RoleLabel;
use App\Models\User;
use App\Services\HubSpot\CustomerCheckService;
use App\Services\HubSpot\OpenRouterService;
use App\Services\HubSpot\QuotePitchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Console extends Component
{
    public string $email = 'vip@remcodesign.nl';

    public string $dealName = 'VIP Website Renewal';

    public string $dealAmount = '12000';

    public string $allowedDiscount = '15';

    /** @var array{is_vip: bool, lifetime_value: int, allowed_discount: int, reason: string, source: string}|null */
    public ?array $customerResult = null;

    /** @var array{text: string, provider: string, generated: bool, model: string|null}|null */
    public ?array $pitchResult = null;

    public string $errorMessage = '';

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
            'dealName' => ['required', 'string', 'max:160'],
            'dealAmount' => ['nullable', 'numeric', 'min:0'],
            'email' => ['required', 'email', 'max:255'],
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

    public function clearResults(): void
    {
        $this->customerResult = null;
        $this->pitchResult = null;
        $this->errorMessage = '';
    }

    public function render(): View
    {
        $openRouterService = app(OpenRouterService::class);

        return view('livewire.admin.hub-spot.console', [
            'aiConfigured' => $openRouterService->isConfigured(),
            'aiModel' => config('ai.providers.openrouter.models.text.default'),
        ]);
    }
}
