<?php

use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_recommendation_tasks', function (Blueprint $blueprint): void {
            $blueprint->ulid('id')->primary();
            $blueprint->string('portal_id');
            $blueprint->string('tenant_id');
            $blueprint->string('deal_id');
            $blueprint->string('callback_id');
            $blueprint->string('workflow_id')->nullable();
            $blueprint->string('action_definition_id')->nullable();
            $blueprint->string('action_definition_version')->nullable();
            $blueprint->string('source');
            $blueprint->string('input_hash');
            $blueprint->enum(
                'status',
                collect(WarehouseRecommendationTaskStatus::cases())
                    ->map(static fn (WarehouseRecommendationTaskStatus $warehouseRecommendationTaskStatus): string => $warehouseRecommendationTaskStatus->value)->all()
            )->default(WarehouseRecommendationTaskStatus::accepted);
            $blueprint->json('result')->nullable();
            $blueprint->string('failure_code')->nullable();
            $blueprint->unsignedInteger('attempts')->default(0);
            $blueprint->timestamp('started_at')->nullable();
            $blueprint->timestamp('completed_at')->nullable();
            $blueprint->timestamp('expires_at')->nullable();
            $blueprint->timestamp('callback_sent_at')->nullable();
            $blueprint->timestamps();

            $blueprint->unique(['portal_id', 'action_definition_version', 'callback_id'], 'warehouse_task_unique_execution');
            $blueprint->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_recommendation_tasks');
    }
};
