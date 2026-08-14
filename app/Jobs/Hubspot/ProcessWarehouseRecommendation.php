<?php

declare(strict_types=1);

namespace App\Jobs\HubSpot;

use App\Enums\WarehouseRecommendationTaskStatus;
use App\Models\HubSpot\WarehouseRecommendationTask;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWarehouseRecommendation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $taskId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = WarehouseRecommendationTask::find($this->taskId);

        if (! $task instanceof WarehouseRecommendationTask) {
            return;
        }

        // Claim the accepted task before any slow external work (Domain D).
        if ($task->status !== WarehouseRecommendationTaskStatus::accepted) {
            return;
        }

        $task->update([
            'status'     => WarehouseRecommendationTaskStatus::processing,
            'started_at' => now(),
        ]);

        // Domain D: CRM reads, Line Item normalization, AI recommendation,
        // Deal note, and callback completion are implemented in a later domain.
    }
}
