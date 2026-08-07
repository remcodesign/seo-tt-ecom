<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Services\HubSpot\QuotePitchService;

it('uses the fallback pitch after OpenRouter generation fails', function (): void {
    config([
        'ai.providers.openrouter.key' => 'test-key',
        'ai.providers.openrouter.models.text.default' => 'test/model',
        'hubspot.ai.timeout' => 15,
    ]);

    QuotePitchAgent::fake(function (): never {
        throw new RuntimeException('Provider request failed.');
    })->preventStrayPrompts();

    expect(app(QuotePitchService::class)->generate(
        dealName: 'VIP Website Renewal',
        dealAmount: 12000,
        customerEmail: 'vip@example.test',
        allowedDiscount: 15,
    ))->toBe([
        'text' => 'For this returning customer we can offer a tailored proposal with up to 15 percent flexibility.',
        'provider' => 'fallback',
        'generated' => false,
        'model' => null,
    ]);
});
