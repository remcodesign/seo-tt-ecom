<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use Illuminate\Contracts\Validation\ValidationRule;
use Livewire\Form as LivewireForm;

class WarehouseWorkflowForm extends LivewireForm
{
    public string $portal_id = '';

    public string $deal_id = '';

    public string $note_deal_id = '';

    public string $callback_id = '';

    public string $workflow_id = '';

    public string $action_definition_version = '3';

    /** @return array<string, list<string|ValidationRule>> */
    public function rules(): array
    {
        return [
            'portal_id'                 => ['required', 'string', 'max:80'],
            'deal_id'                   => ['required', 'string', 'max:80'],
            'note_deal_id'              => ['required', 'string', 'max:80'],
            'callback_id'               => ['nullable', 'string', 'max:160'],
            'workflow_id'               => ['nullable', 'string', 'max:160'],
            'action_definition_version' => ['required', 'string', 'max:40'],
        ];
    }
}
