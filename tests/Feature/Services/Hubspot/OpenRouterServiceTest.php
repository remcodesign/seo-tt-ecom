<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Services\HubSpot\OpenRouterService;
use Laravel\Ai\Exceptions\RateLimitedException;

describe('OpenRouterService configuration', function (): void {
    it('returns null when OpenRouter is not configured', function (): void {
        config([
            'ai.providers.openrouter.key' => null,
            'ai.providers.openrouter.models.text.default' => 'test/model',
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
    });

    it('returns null when no models are configured', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => '',
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
    });

    it('returns null when the model configuration is not a string', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => ['test/model'],
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
    });

    it('returns null when the timeout is not an integer', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout' => '15',
        ]);

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
    });
});

describe('OpenRouterService generation failures', function (): void {
    beforeEach(function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout' => 15,
        ]);
    });

    it('returns null when the agent throws an ordinary exception', function (): void {
        QuotePitchAgent::fake(function (): never {
            throw new RuntimeException('Provider request failed.');
        })->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
    });

    it('returns null when the agent returns empty text', function (): void {
        QuotePitchAgent::fake(['   '])->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
    });

    it('returns null when the agent returns text longer than the limit', function (): void {
        QuotePitchAgent::fake([str_repeat('a', 1201)])->preventStrayPrompts();

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
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

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))->toBeNull();
        expect($attempts)->toBe(2);
    });
});

describe('OpenRouterService generation success', function (): void {
    beforeEach(function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/model',
            'hubspot.ai.timeout' => 15,
        ]);
    });

    it('returns trimmed generated text with the configured model and usage', function (): void {
        QuotePitchAgent::fake(['  A generated pitch.  '])->preventStrayPrompts();

        $result = app(OpenRouterService::class)->generate('Write a pitch.');

        expect($result)
            ->toBeArray()
            ->toMatchArray([
                'text' => 'A generated pitch.',
                'model' => 'test/model',
            ])
            ->and($result['usage'])->toBeArray();
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

        expect(app(OpenRouterService::class)->generate('Write a pitch.'))
            ->toMatchArray([
                'text' => 'A backup model pitch.',
                'model' => 'test/backup',
            ]);
        expect($attempts)->toBe(2);
    });
});
