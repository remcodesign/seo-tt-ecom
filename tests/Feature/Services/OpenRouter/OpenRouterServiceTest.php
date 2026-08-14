<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Ai\Agents\HubSpot\WarehouseRecommendationAgent;
use App\Data\OpenRouter\Responses\OpenRouterDataResponse;
use App\Enums\AiModelProfile;
use App\Services\OpenRouter\OpenRouterService;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Exceptions\RateLimitedException;

describe('OpenRouterService configuration', function (): void {
    it('returns null when OpenRouter is not configured', function (): void {
        config([
            'ai.providers.openrouter.key'                 => null,
            'ai.providers.openrouter.models.text.default' => 'test/model',
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
    });

    it('returns null when no models are configured', function (): void {
        config([
            'ai.providers.openrouter.key'                 => 'test-key',
            'ai.providers.openrouter.models.text.default' => '',
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
    });

    it('returns null when the model configuration is not a string', function (): void {
        config([
            'ai.providers.openrouter.key'                 => 'test-key',
            'ai.providers.openrouter.models.text.default' => ['test/model'],
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
    });

    it('returns null when the timeout is not an integer', function (): void {
        config([
            'ai.providers.openrouter.key'                 => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout'                          => '15',
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
    });

    it('allows the caller to override the default timeout', function (): void {
        config([
            'ai.providers.openrouter.key'                 => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout'                          => 'invalid',
        ]);

        QuotePitchAgent::fake(['A generated pitch.'])->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate(
            'Write a pitch.',
            QuotePitchAgent::make(),
            timeout: 60,
        ))->toMatchArray(['text' => 'A generated pitch.']);
    });
});

describe('OpenRouterService generation failures', function (): void {
    beforeEach(function (): void {
        config([
            'ai.providers.openrouter.key'                 => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout'                          => 15,
        ]);
    });

    it('returns null when the agent throws an ordinary exception', function (): void {
        QuotePitchAgent::fake(function (): never {
            throw new RuntimeException('Provider request failed.');
        })->preventStrayPrompts();

        $openRouterService = app(OpenRouterService::class);

        expect($openRouterService->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull()
            ->and($openRouterService->lastError())->toContain('Provider request failed.');
    });

    it('returns null when the agent returns empty text', function (): void {
        QuotePitchAgent::fake(['   '])->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
    });

    it('returns null when the agent returns text longer than the limit', function (): void {
        QuotePitchAgent::fake([str_repeat('a', 1201)])->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
    });

    it('returns null when every configured model is rate limited', function (): void {
        config([
            'ai.providers.openrouter.models.text.default' => 'test/primary,test/backup',
        ]);

        $attempts = 0;
        QuotePitchAgent::fake(function () use (&$attempts): never {
            $attempts++;

            throw RateLimitedException::forProvider('openrouter', 429);
        })->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make()))->toBeNull();
        expect($attempts)->toBe(2);
    });
});

describe('OpenRouterService generation success', function (): void {
    beforeEach(function (): void {
        config([
            'ai.providers.openrouter.key'                 => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout'                          => 15,
        ]);
    });

    it('returns trimmed generated text with the configured model and usage', function (): void {
        QuotePitchAgent::fake(['  A generated pitch.  '])->preventStrayPrompts();
        $loggedEvent = null;
        Event::listen(MessageLogged::class, function (MessageLogged $messageLogged) use (&$loggedEvent): void {
            if ($messageLogged->message === 'OpenRouter request completed.') {
                $loggedEvent = $messageLogged;
            }
        });

        $result = app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make());

        expect($result)
            ->toBeInstanceOf(OpenRouterDataResponse::class)
            ->text->toBe('A generated pitch.')
            ->model->toBe('test/model')
            ->usage->toBeArray();

        expect($loggedEvent)->toBeInstanceOf(MessageLogged::class)
            ->and($loggedEvent->context['duration_ms'] ?? null)->toBeInt();
    });

    it('uses the next configured model after a failoverable error', function (): void {
        config([
            'ai.providers.openrouter.models.text.default' => 'test/primary,test/backup',
        ]);

        $attempts = 0;
        QuotePitchAgent::fake(function () use (&$attempts): string {
            $attempts++;

            if ($attempts === 1) {
                throw RateLimitedException::forProvider('openrouter', 429);
            }

            return 'A backup model pitch.';
        })->preventStrayPrompts();

        $result = app(OpenRouterService::class)->generate('Write a pitch.', QuotePitchAgent::make());

        expect($result)
            ->text->toBe('A backup model pitch.')
            ->model->toBe('test/backup');
        expect($attempts)->toBe(2);
    });

    it('uses the smart model list for warehouse recommendations', function (): void {
        config([
            'ai.providers.openrouter.models.text.default' => 'test/simple-model',
            'ai.providers.openrouter.models.text.smart'   => 'test/smart-primary,test/smart-backup',
        ]);

        WarehouseRecommendationAgent::fake([[
            'selected_warehouse' => ['id' => 'warehouse-local', 'name' => 'Local City Warehouse'],
            'reason'             => 'The local warehouse can fulfil the request.',
        ]])->preventStrayPrompts();

        $result = app(OpenRouterService::class)->generate(
            'Choose a warehouse.',
            WarehouseRecommendationAgent::make(),
            AiModelProfile::Smart,
        );

        expect($result->model)->toBe('test/smart-primary');
    });

    it('fails over to the next smart model after a rate limit', function (): void {
        config([
            'ai.providers.openrouter.models.text.default' => 'test/simple-model',
            'ai.providers.openrouter.models.text.smart'   => 'test/smart-primary,test/smart-backup',
        ]);

        $attempts = 0;
        WarehouseRecommendationAgent::fake(function () use (&$attempts): array {
            $attempts++;

            if ($attempts === 1) {
                throw RateLimitedException::forProvider('openrouter', 429);
            }

            return [
                'selected_warehouse' => ['id' => 'warehouse-local', 'name' => 'Local City Warehouse'],
                'reason'             => 'The local warehouse can fulfil the request.',
            ];
        })->preventStrayPrompts();

        $result = app(OpenRouterService::class)->generate(
            'Choose a warehouse.',
            WarehouseRecommendationAgent::make(),
            AiModelProfile::Smart,
        );

        expect($result->model)->toBe('test/smart-backup');
        expect($attempts)->toBe(2);
    });
});
