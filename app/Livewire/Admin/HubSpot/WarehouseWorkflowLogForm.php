<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use Illuminate\Contracts\Validation\ValidationRule;
use Livewire\Form as LivewireForm;

class WarehouseWorkflowLogForm extends LivewireForm
{
    public string $search = '';

    public string $status = 'all';

    public string $source = 'all';

    public string $date_filter = 'today';

    /** @return array<string, list<string|ValidationRule>> */
    public function rules(): array
    {
        return [
            'search'      => ['nullable', 'string', 'max:160'],
            'status'      => ['required', 'string', 'max:40'],
            'source'      => ['required', 'string', 'max:80'],
            'date_filter' => ['required', 'string', 'max:40'],
        ];
    }
}
