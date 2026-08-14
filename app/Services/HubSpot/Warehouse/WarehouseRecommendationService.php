<?php

declare(strict_types=1);

namespace App\Services\HubSpot\Warehouse;

use App\Ai\Agents\HubSpot\WarehouseRecommendationAgent;
use App\Data\HubSpot\Data\WarehouseCandidateData;
use App\Data\HubSpot\Requests\WarehouseRecommendationData;
use App\Data\HubSpot\Responses\WarehouseRecommendationDataResponse;
use App\Data\HubSpot\Responses\WarehouseSelectionData;
use App\Data\OpenRouter\Responses\OpenRouterDataResponse;
use App\Enums\AiModelProfile;
use App\Services\OpenRouter\OpenRouterService;

final readonly class WarehouseRecommendationService
{
    public function __construct(private OpenRouterService $openRouterService) {}

    public function recommend(WarehouseRecommendationData $warehouseRecommendationData): WarehouseRecommendationDataResponse
    {
        $candidates = $this->candidates();
        $prompt = $this->prompt($warehouseRecommendationData, $candidates);
        $configuredTimeout = config('hubspot.ai.smart_timeout', 60);
        $smartTimeout = is_int($configuredTimeout) ? $configuredTimeout : 60;
        $generated = $this->openRouterService->generate(
            $prompt,
            WarehouseRecommendationAgent::make(),
            // AiModelProfile::Smart,
            AiModelProfile::Default,
            timeout: $smartTimeout,
        );
        $selectedWarehouse = $this->selectedWarehouse($generated?->structured, $warehouseRecommendationData->requested_quantity, $candidates);
        $aiGenerated = $selectedWarehouse instanceof WarehouseSelectionData && $generated instanceof OpenRouterDataResponse;
        $reason = $this->reason($generated, $selectedWarehouse);
        $error = $this->error($generated, $selectedWarehouse, $warehouseRecommendationData->requested_quantity);

        return new WarehouseRecommendationDataResponse(
            sku: $warehouseRecommendationData->sku,
            requested_quantity: $warehouseRecommendationData->requested_quantity,
            destination_postal_code: $warehouseRecommendationData->destination_postal_code,
            selected_warehouse: $selectedWarehouse,
            ai_generated: $aiGenerated,
            reason: $reason ?? ($error ?? 'No warehouse was selected.'),
            error: $error,
            raw_ai_output: $generated?->text,
            candidates: $candidates,
        );
    }

    /** @return list<WarehouseCandidateData> */
    private function candidates(): array
    {
        // todo: replace with real warehouse candidates from database or API
        // public string $id,
        // public string $name,
        // public int $available_quantity,
        // public int $distance_km,
        // public int $delivery_days,
        // public float $review_score,
        return [
            new WarehouseCandidateData('warehouse-local', 'Local City Warehouse', 3, 8, 1, 4.5),
            new WarehouseCandidateData('warehouse-premium', 'Premium Fulfillment Warehouse', 4, 15, 2, 4.9),
            new WarehouseCandidateData('warehouse-partial', 'Partial Stock Warehouse', 1, 5, 1, 4.4),
        ];
    }

    /**
     * @param  list<WarehouseCandidateData>  $candidates
     */
    private function prompt(WarehouseRecommendationData $warehouseRecommendationData, array $candidates): string
    {
        return sprintf(
            "Choose the best single warehouse for SKU %s. Requested quantity: %d. A warehouse is valid when available_quantity is greater than or equal to %d; exact stock is not required. Destination postal code: %s. Return structured JSON with selected_warehouse.id, selected_warehouse.name, and reason. The id must exactly match a candidate id and the name must exactly match that candidate's name. Candidates:\n%s",
            $warehouseRecommendationData->sku,
            $warehouseRecommendationData->requested_quantity,
            $warehouseRecommendationData->requested_quantity,
            $warehouseRecommendationData->destination_postal_code,
            json_encode(collect($candidates)
                ->map(static fn (WarehouseCandidateData $warehouseCandidateData): array => $warehouseCandidateData->toArray())->all(), JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param  array<string, mixed>|null  $selection
     * @param  list<WarehouseCandidateData>  $candidates
     */
    private function selectedWarehouse(?array $selection, int $requestedQuantity, array $candidates): ?WarehouseSelectionData
    {
        $selectedWarehouse = $selection['selected_warehouse'] ?? null;
        $selectedId = is_array($selectedWarehouse) ? ($selectedWarehouse['id'] ?? null) : null;

        if (is_string($selectedId) && $this->canFulfil($selectedId, $requestedQuantity, $candidates)) {
            foreach ($candidates as $candidate) {
                if ($candidate->id === $selectedId) {
                    return new WarehouseSelectionData($selectedId, $candidate->name);
                }
            }
        }

        return null;
    }

    /** @param list<WarehouseCandidateData> $candidates */
    private function canFulfil(string $warehouseId, int $requestedQuantity, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            $candidateId = $candidate->id;
            $availableQuantity = $candidate->available_quantity;

            if ($candidateId === $warehouseId && $availableQuantity >= $requestedQuantity) {
                return true;
            }
        }

        return false;
    }

    private function error(?OpenRouterDataResponse $openRouterDataResponse, ?WarehouseSelectionData $warehouseSelectionData, int $requestedQuantity): ?string
    {
        if (! $openRouterDataResponse instanceof OpenRouterDataResponse) {
            return $this->openRouterService->lastError() ?? 'OpenRouter returned no response.';
        }

        if ($warehouseSelectionData instanceof WarehouseSelectionData) {
            return null;
        }

        return sprintf('AI returned no valid warehouse selection for quantity %d.', $requestedQuantity);
    }

    private function reason(?OpenRouterDataResponse $openRouterDataResponse, ?WarehouseSelectionData $warehouseSelectionData): ?string
    {
        if (! $warehouseSelectionData instanceof WarehouseSelectionData || ! $openRouterDataResponse instanceof OpenRouterDataResponse) {
            return null;
        }

        $reason = $openRouterDataResponse->structured['reason'] ?? null;

        if (! is_string($reason)) {
            $nestedWarehouse = $openRouterDataResponse->structured['selected_warehouse'] ?? null;
            $nestedReason = is_array($nestedWarehouse) ? ($nestedWarehouse['reason'] ?? null) : null;
            $reason = is_string($nestedReason) ? $nestedReason : null;
        }

        return $reason;
    }
}
