<?php

declare(strict_types=1);

use App\Http\Middleware\LogHubSpotWebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

it('does not log when request logging is disabled', function (): void {
    config(['hubspot.request_logging.enabled' => false]);

    $response = new Response('ok', 202);

    expect((new LogHubSpotWebhookRequest)->handle(
        Request::create('/api/hubspot/test', 'POST'),
        fn (): Response => $response,
    ))->toBe($response);
});

it('logs an empty request body as a null payload', function (): void {
    config(['hubspot.request_logging.enabled' => true]);

    $mock = Mockery::mock();
    $capturedContext = null;
    Log::shouldReceive('channel')->once()->with('hubspot')->andReturn($mock);
    $mock->shouldReceive('info')
        ->once()
        ->with('HubSpot webhook request captured.', Mockery::on(function (array $context) use (&$capturedContext): bool {
            $capturedContext = $context;

            return true;
        }));

    $response = (new LogHubSpotWebhookRequest)->handle(
        Request::create('/api/hubspot/test', 'POST'),
        fn (): Response => new Response('', 204),
    );

    assert(is_array($capturedContext));

    expect($response->getStatusCode())->toBe(204)
        ->and($capturedContext['payload'])->toBeNull()
        ->and($capturedContext['raw_body'])->toBe('')
        ->and($capturedContext['response_status'])->toBe(204);
});

it('logs invalid JSON without rejecting the downstream response', function (): void {
    config(['hubspot.request_logging.enabled' => true]);

    $mock = Mockery::mock();
    $capturedContext = null;
    Log::shouldReceive('channel')->once()->with('hubspot')->andReturn($mock);
    $mock->shouldReceive('info')
        ->once()
        ->with('HubSpot webhook request captured.', Mockery::on(function (array $context) use (&$capturedContext): bool {
            $capturedContext = $context;

            return true;
        }));

    $response = (new LogHubSpotWebhookRequest)->handle(
        Request::create('/api/hubspot/test', 'POST', [], [], [], [], '{invalid-json'),
        fn (): Response => new Response('accepted', 202),
    );

    assert(is_array($capturedContext));

    expect($response->getStatusCode())->toBe(202)
        ->and($capturedContext['payload'])->toBe('[invalid JSON]')
        ->and($capturedContext['raw_body'])->toBe('{invalid-json')
        ->and($capturedContext['response_status'])->toBe(202);
});

it('logs a null response status and rethrows downstream exceptions', function (): void {
    config(['hubspot.request_logging.enabled' => true]);

    $mock = Mockery::mock();
    $capturedContext = null;
    Log::shouldReceive('channel')->once()->with('hubspot')->andReturn($mock);
    $mock->shouldReceive('info')
        ->once()
        ->with('HubSpot webhook request captured.', Mockery::on(function (array $context) use (&$capturedContext): bool {
            $capturedContext = $context;

            return true;
        }));

    expect(fn (): Response => (new LogHubSpotWebhookRequest)->handle(
        Request::create('/api/hubspot/test', 'POST', [], [], [], [], '{"event":"test"}'),
        function (): Response {
            throw new RuntimeException('downstream failure');
        },
    ))->toThrow(RuntimeException::class, 'downstream failure');

    assert(is_array($capturedContext));

    expect($capturedContext['payload'])->toBe(['event' => 'test'])
        ->and($capturedContext['response_status'])->toBeNull();
});
