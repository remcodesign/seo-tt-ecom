<?php

declare(strict_types=1);

use App\Enums\WarehouseRecommendationTaskStatus;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Models\HubSpot\WarehouseRecommendationTask;

it('claims an accepted task as processing and records the start time', function (): void {
    $task = WarehouseRecommendationTask::factory()->create([
        'status'     => WarehouseRecommendationTaskStatus::accepted,
        'started_at' => null,
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::processing)
        ->and($task->started_at)->not->toBeNull();
});

it('does nothing when the task does not exist', function (): void {
    (new ProcessWarehouseRecommendation('does-not-exist'))->handle();

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('does not claim a task that is not accepted', function (): void {
    $task = WarehouseRecommendationTask::factory()->create([
        'status'     => WarehouseRecommendationTaskStatus::processing,
        'started_at' => null,
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::processing)
        ->and($task->started_at)->toBeNull();
});
