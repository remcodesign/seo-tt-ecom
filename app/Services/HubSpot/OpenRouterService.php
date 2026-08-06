<?php

declare(strict_types=1);

namespace App\Services\HubSpot;

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Throwable;

final class OpenRouterService
{
    /**
     * @return array{text: string, model: string, usage: array<string, mixed>}|null
     */
    public function generate(string $userPrompt): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $model = config('ai.providers.openrouter.models.text.default');

        if (! is_string($model) || $model === '') {
            return null;
        }

        try {
            $timeout = config('hubspot.ai.timeout', 15);

            if (! is_int($timeout)) {
                return null;
            }

            $response = QuotePitchAgent::make()->prompt(
                $userPrompt,
                provider: Lab::OpenRouter,
                model: $model,
                timeout: $timeout,
            );
        } catch (Throwable $throwable) {
            Log::channel('ai')->warning('Laravel AI agent request failed.', [
                'exception' => $throwable::class,
                'model' => $model,
            ]);

            return null;
        }

        $text = trim($response->text);

        if ($text === '' || mb_strlen($text) > 1200) {
            Log::channel('ai')->warning('OpenRouter returned unusable text.', [
                'model' => $model,
                'text_length' => mb_strlen($text),
            ]);

            return null;
        }

        Log::channel('ai')->info('OpenRouter request completed.', [
            'sdk' => 'laravel/ai',
            'model' => $model,
            'usage' => $response->usage->toArray(),
            'text_length' => mb_strlen($text),
        ]);

        return [
            'text' => $text,
            'model' => $model,
            'usage' => $response->usage->toArray(),
        ];
    }

    public function isConfigured(): bool
    {
        $apiKey = config('ai.providers.openrouter.key');
        $model = config('ai.providers.openrouter.models.text.default');

        return is_string($apiKey) && $apiKey !== '' && is_string($model) && $model !== '';
    }
}
