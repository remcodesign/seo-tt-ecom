<?php

declare(strict_types=1);

namespace App\Models\HubSpot;

use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use Carbon\CarbonImmutable;
use Database\Factories\HubSpot\WarehouseRecommendationTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $portal_id
 * @property string $tenant_id
 * @property string $deal_id
 * @property string $callback_id
 * @property string|null $workflow_id
 * @property string|null $action_definition_id
 * @property string|null $action_definition_version
 * @property string $source
 * @property string $input_hash
 * @property WarehouseRecommendationTaskStatus $status
 * @property array<string, mixed>|null $result
 * @property string|null $failure_code
 * @property int $attempts
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $callback_sent_at
 * @property string|null $note_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'portal_id',
    'tenant_id',
    'deal_id',
    'callback_id',
    'workflow_id',
    'action_definition_id',
    'action_definition_version',
    'source',
    'input_hash',
    'status',
    'result',
    'failure_code',
    'attempts',
    'started_at',
    'completed_at',
    'expires_at',
    'callback_sent_at',
    'note_id',
])]
#[Table(name: 'warehouse_recommendation_tasks')]
class WarehouseRecommendationTask extends Model
{
    /** @use HasFactory<WarehouseRecommendationTaskFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'status'           => WarehouseRecommendationTaskStatus::class,
            'result'           => 'array',
            'attempts'         => 'integer',
            'started_at'       => 'immutable_datetime',
            'completed_at'     => 'immutable_datetime',
            'expires_at'       => 'immutable_datetime',
            'callback_sent_at' => 'immutable_datetime',
        ];
    }
}
