<?php

declare(strict_types=1);

namespace App\Ai\Agents\HubSpot;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class WarehouseRecommendationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'Choose exactly one warehouse from the supplied candidates. A candidate is valid when available_quantity is greater than or equal to requested quantity; exact stock is not required, so a warehouse with 3 or 4 units is valid for a request of 2. A candidate with less stock is invalid. Return the selected candidate id and its display name in the structured output. Explain the choice in under 120 words, balancing delivery time, distance, stock buffer, and review score. Never invent facts, split the order, or return a warehouse not present in the candidates.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'selected_warehouse' => $schema->object(fn (JsonSchema $jsonSchema): array => [
                'id' => $jsonSchema->string()->required(),
                'name' => $jsonSchema->string()->required(),
            ])->required(),
            'reason' => $schema->string()->required(),
        ];
    }
}
