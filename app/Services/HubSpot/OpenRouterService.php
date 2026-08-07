<?php

declare(strict_types=1);

namespace App\Services\HubSpot;

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\FailoverableException;
use Throwable;

final class OpenRouterService
{
    /**
     * @return array{text: string, model: string, usage: array<string, mixed>}|null
     */
    public function generate(string $userPrompt): ?array
    {
        if ($this->configuredApiKey() === null) {
            return null;
        }

        $models = $this->configuredModels();

        if ($models === []) {
            return null;
        }

        $timeout = config('hubspot.ai.timeout', 15);

        if (! is_int($timeout)) {
            return null;
        }

        foreach ($models as $model) {
            try {
                $result = $this->generateForModel($userPrompt, $model, $timeout);
            } catch (FailoverableException $exception) {
                Log::channel('ai')->warning('Laravel AI agent request failed; trying next model.', [
                    'exception' => $exception::class,
                    'model' => $model,
                ]);

                continue;
            }

            return $result;
        }

        return null;
    }

    public function isConfigured(): bool
    {
        $models = $this->configuredModels();

        return $this->configuredApiKey() !== null && $models !== [];
    }

    private function configuredApiKey(): ?string
    {
        $apiKey = config('ai.providers.openrouter.key');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    /**
     * @return array{text: string, model: string, usage: array<string, mixed>}|null
     */
    private function generateForModel(string $userPrompt, string $model, int $timeout): ?array
    {
        try {
            $response = QuotePitchAgent::make()->prompt(
                $userPrompt,
                provider: Lab::OpenRouter,
                model: $model,
                timeout: $timeout,
            );
        } catch (Throwable $throwable) {
            if ($throwable instanceof FailoverableException) {
                throw $throwable;
            }

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

    /**
     * @return list<string>
     */
    private function configuredModels(): array
    {
        $models = config('ai.providers.openrouter.models.text.default');

        if (! is_string($models)) {
            return [];
        }

        /** @var list<string> $configuredModels */
        $configuredModels = collect(explode(',', $models))
            ->map(static fn (string $model, int $key): string => trim($model))
            ->filter()
            ->values()
            ->all();

        return $configuredModels;
    }
}
