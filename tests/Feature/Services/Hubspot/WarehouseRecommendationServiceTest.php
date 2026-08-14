<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\WarehouseRecommendationAgent;
use App\Data\HubSpot\Requests\WarehouseRecommendationData;
use App\Services\HubSpot\Warehouse\WarehouseRecommendationService;
use Laravel\Ai\Prompts\AgentPrompt;

describe('WarehouseRecommendationService', function (): void {
    it('lets the AI choose one eligible warehouse for the full quantity', function (): void {
        config([
            'ai.providers.openrouter.key'               => 'test-key',
            'ai.providers.openrouter.models.text.smart' => 'test/smart-model',
        ]);

        WarehouseRecommendationAgent::fake([
            [
                'selected_warehouse' => [
                    'id'     => 'warehouse-premium',
                    'name'   => 'Premium Fulfillment Warehouse',
                    'reason' => 'The premium warehouse has the strongest reviews while fulfilling all requested units.',
                ],
            ],
        ])->preventStrayPrompts();

        $warehouseRecommendationDataResponse = app(WarehouseRecommendationService::class)->recommend(
            new WarehouseRecommendationData('SKU-123', 2, '1234AB'),
        );

        expect($warehouseRecommendationDataResponse->selected_warehouse?->id)->toBe('warehouse-premium')
            ->and($warehouseRecommendationDataResponse->selected_warehouse?->name)->toBe('Premium Fulfillment Warehouse')
            ->and($warehouseRecommendationDataResponse->ai_generated)->toBeTrue()
            ->and($warehouseRecommendationDataResponse->raw_ai_output)->toBe(json_encode([
                'selected_warehouse' => [
                    'id'     => 'warehouse-premium',
                    'name'   => 'Premium Fulfillment Warehouse',
                    'reason' => 'The premium warehouse has the strongest reviews while fulfilling all requested units.',
                ],
            ]));

        WarehouseRecommendationAgent::assertPrompted(fn (AgentPrompt $agentPrompt): bool => $agentPrompt->contains('exact stock is not required')
            && $agentPrompt->contains('structured JSON')
            && $agentPrompt->contains('available_quantity'));
    });

    it('allows the AI to choose the partial warehouse when one item is requested', function (): void {
        config([
            'ai.providers.openrouter.key'               => 'test-key',
            'ai.providers.openrouter.models.text.smart' => 'test/smart-model',
        ]);

        WarehouseRecommendationAgent::fake([[
            'selected_warehouse' => [
                'id'     => 'warehouse-partial',
                'name'   => 'Partial Stock Warehouse',
                'reason' => 'One unit is available at the closest warehouse.',
            ],
        ]])->preventStrayPrompts();

        $warehouseRecommendationDataResponse = app(WarehouseRecommendationService::class)->recommend(
            new WarehouseRecommendationData('SKU-123', 1, '1234AB'),
        );

        expect($warehouseRecommendationDataResponse->selected_warehouse?->id)->toBe('warehouse-partial')
            ->and($warehouseRecommendationDataResponse->selected_warehouse?->name)->toBe('Partial Stock Warehouse')
            ->and($warehouseRecommendationDataResponse->ai_generated)->toBeTrue();
    });

    it('rejects an AI-selected warehouse that cannot fulfil the requested quantity', function (): void {
        config([
            'ai.providers.openrouter.key'               => 'test-key',
            'ai.providers.openrouter.models.text.smart' => 'test/smart-model',
        ]);

        WarehouseRecommendationAgent::fake([[
            'selected_warehouse' => [
                'id'     => 'warehouse-partial',
                'name'   => 'Partial Stock Warehouse',
                'reason' => 'It is the closest warehouse.',
            ],
        ]])->preventStrayPrompts();

        $warehouseRecommendationDataResponse = app(WarehouseRecommendationService::class)->recommend(
            new WarehouseRecommendationData('SKU-123', 2, '1234AB'),
        );

        expect($warehouseRecommendationDataResponse->selected_warehouse)->toBeNull()
            ->and($warehouseRecommendationDataResponse->ai_generated)->toBeFalse()
            ->and($warehouseRecommendationDataResponse->reason)->toBe('AI returned no valid warehouse selection for quantity 2.')
            ->and($warehouseRecommendationDataResponse->error)->toBe('AI returned no valid warehouse selection for quantity 2.');
    });

    it('exposes the captured AI error when warehouse recommendation is unavailable', function (): void {
        config([
            'ai.providers.openrouter.key'               => null,
            'ai.providers.openrouter.models.text.smart' => '',
        ]);

        $warehouseRecommendationDataResponse = app(WarehouseRecommendationService::class)->recommend(
            new WarehouseRecommendationData('SKU-123', 2, '1234AB'),
        );

        expect($warehouseRecommendationDataResponse->selected_warehouse)->toBeNull()
            ->and($warehouseRecommendationDataResponse->ai_generated)->toBeFalse()
            ->and($warehouseRecommendationDataResponse->reason)->toBe('OpenRouter is not configured.')
            ->and($warehouseRecommendationDataResponse->error)->toBe('OpenRouter is not configured.');
    });
});
