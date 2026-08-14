<?php

declare(strict_types=1);

namespace App\Services\OpenRouter;

use App\Data\OpenRouter\Responses\OpenRouterDataResponse;
use App\Enums\OpenRouter\AiModelProfile;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final class OpenRouterService
{
    private ?string $lastError = null;

    public function generate(
        string $userPrompt,
        Agent $agent,
        AiModelProfile $aiModelProfile = AiModelProfile::Default,
        ?int $timeout = null,
    ): ?OpenRouterDataResponse {
        $this->lastError = null;

        if ($this->configuredApiKey() === null) {
            $this->lastError = 'OpenRouter is not configured.';

            return null;
        }

        $models = $this->configuredModels($aiModelProfile);

        if ($models === []) {
            $this->lastError = 'No OpenRouter model is configured.';

            return null;
        }

        $timeout ??= config('hubspot.ai.timeout', 15);

        if (! is_int($timeout) || $timeout < 1) {
            $this->lastError = 'The AI timeout configuration is invalid.';

            return null;
        }

        foreach ($models as $model) {
            try {
                $result = $this->generateForModel($userPrompt, $model, $timeout, $agent);
            } catch (FailoverableException $exception) {
                $this->lastError = $this->errorMessage($exception);

                Log::channel('ai')->warning('Laravel AI agent request failed; trying next model.', [
                    'exception' => $exception::class,
                    'model'     => $model,
                ]);

                continue;
            }

            return $result;
        }

        return null;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function isConfigured(): bool
    {
        $models = $this->configuredModels(AiModelProfile::Default);

        return $this->configuredApiKey() !== null && $models !== [];
    }

    private function configuredApiKey(): ?string
    {
        $apiKey = config('ai.providers.openrouter.key');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    private function generateForModel(string $userPrompt, string $model, int $timeout, Agent $agent): ?OpenRouterDataResponse
    {
        $startedAt = hrtime(true);

        try {
            $response = $agent->prompt(
                $userPrompt,
                // todo - (future) - allow the caller to specify the provider and model, for now we just use OpenRouter
                // also make this service class more generic, so it can be used for other providers in the future
                // also the folder structure should be more generic, so it can be used for other providers in the future
                provider: Lab::OpenRouter,
                model: $model,
                timeout: $timeout,
            );
        } catch (Throwable $throwable) {
            if ($throwable instanceof FailoverableException) {
                throw $throwable;
            }

            $this->lastError = $this->errorMessage($throwable);

            Log::channel('ai')->warning('Laravel AI agent request failed.', [
                'exception'   => $throwable::class,
                'model'       => $model,
                'duration_ms' => $this->durationMilliseconds($startedAt),
            ]);

            return null;
        }

        $text = trim($response->text);

        if ($text === '' || mb_strlen($text) > 1200) {
            $this->lastError = 'OpenRouter returned an empty or oversized response.';

            Log::channel('ai')->warning('OpenRouter returned unusable text.', [
                'model'       => $model,
                'text_length' => mb_strlen($text),
                'duration_ms' => $this->durationMilliseconds($startedAt),
            ]);

            return null;
        }

        Log::channel('ai')->info('OpenRouter request completed.', [
            'sdk'         => 'laravel/ai',
            'model'       => $model,
            'usage'       => $response->usage->toArray(),
            'text_length' => mb_strlen($text),
            'duration_ms' => $this->durationMilliseconds($startedAt),
        ]);

        return new OpenRouterDataResponse(
            text: $text,
            model: $model,
            usage: $response->usage->toArray(),
            structured: $response instanceof StructuredAgentResponse ? $response->toArray() : null,
        );
    }

    /**
     * @return list<string>
     */
    private function configuredModels(AiModelProfile $aiModelProfile): array
    {
        $configKey = $aiModelProfile === AiModelProfile::Smart
            ? 'ai.providers.openrouter.models.text.smart'
            : 'ai.providers.openrouter.models.text.default';
        $models = config($configKey);

        if (! is_string($models)) {
            return [];
        }

        /** @var list<string> $configuredModels */
        $configuredModels = collect(explode(',', $models))
            ->map(static fn (string $model): string => trim($model))
            ->filter()
            ->values()
            ->all();

        return $configuredModels;
    }

    private function errorMessage(Throwable $throwable): string
    {
        return sprintf('%s: %s', $throwable::class, $throwable->getMessage());
    }

    private function durationMilliseconds(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
