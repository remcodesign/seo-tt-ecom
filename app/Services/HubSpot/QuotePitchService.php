<?php

declare(strict_types=1);

namespace App\Services\HubSpot;

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Data\OpenRouter\Responses\OpenRouterDataResponse;
use App\Enums\OpenRouter\AiModelProfile;
use App\Services\OpenRouter\OpenRouterService;
use Illuminate\Support\Facades\Log;

final readonly class QuotePitchService
{
    public function __construct(
        private OpenRouterService $openRouterService,
    ) {}

    /**
     * @return array{text: string, provider: string, generated: bool, model: string|null}
     */
    public function generate(
        string $dealName,
        ?float $dealAmount,
        string $customerEmail,
        int $allowedDiscount,
    ): array {
        // todo add DTO response object, also cleaner with PHPStan

        $fallback = $this->fallback($allowedDiscount);

        if (! $this->openRouterService->isConfigured()) {
            Log::channel('ai')->info('Quote pitch used fallback because OpenRouter is not configured.');

            return $fallback;
        }

        $result = $this->openRouterService->generate(
            sprintf(
                "Deal name: %s\nDeal amount: %s\nCustomer email: %s\nMaximum discount: %d percent",
                $dealName,
                $dealAmount === null ? 'unknown' : (string) $dealAmount,
                $customerEmail,
                $allowedDiscount,
            ),
            QuotePitchAgent::make(),
            AiModelProfile::Default,
        );

        if (! $result instanceof OpenRouterDataResponse) {
            Log::channel('ai')->warning('Quote pitch used fallback after OpenRouter failure.');

            return $fallback;
        }

        return [
            'text'      => $result->text,
            'provider'  => 'openrouter',
            'generated' => true,
            'model'     => $result->model,
        ];
    }

    /**
     * @return array{text: string, provider: string, generated: bool, model: null}
     */
    private function fallback(int $allowedDiscount): array
    {
        // todo add DTO response object, also cleaner with PHPStan
        return [
            'text' => sprintf(
                'For this returning customer we can offer a tailored proposal with up to %d percent flexibility.',
                $allowedDiscount,
            ),
            'provider'  => 'fallback',
            'generated' => false,
            'model'     => null,
        ];
    }
}
