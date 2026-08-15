<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveHubSpotTenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    config([
        'hubspot.portal_tenants' => [
            '12345' => ['tenant_id' => 'tenant-test', 'enabled' => true],
            '67890' => ['tenant_id' => 'tenant-disabled', 'enabled' => false],
        ],
    ]);
});

it('resolves a numeric portal ID and normalizes it for downstream consumers', function (): void {
    $request = portalRequest(12345);

    $response = app(ResolveHubSpotTenant::class)->handle($request, function (Request $request): Response {
        expect($request->attributes->get('hubspot_portal_id'))->toBe('12345')
            ->and($request->attributes->get('hubspot_tenant_id'))->toBe('tenant-test')
            ->and($request->input('origin.portalId'))->toBe('12345');

        return response()->noContent();
    });

    expect($response->getStatusCode())->toBe(204);
});

it('normalizes numeric workflow IDs for DTO validation', function (): void {
    $request = Request::create('/api/hubspot/warehouse-recommendation-v3', 'POST', [
        'origin' => [
            'portalId'                => 12345,
            'actionDefinitionId'      => 270666375,
            'actionDefinitionVersion' => 2,
        ],
        'context' => ['workflowId' => 4720693460],
        'object'  => ['objectId' => 516930743536],
    ]);

    app(ResolveHubSpotTenant::class)->handle($request, function (Request $request): Response {
        expect($request->input('origin.actionDefinitionId'))->toBe('270666375')
            ->and($request->input('origin.actionDefinitionVersion'))->toBe('2')
            ->and($request->input('context.workflowId'))->toBe('4720693460')
            ->and($request->input('object.objectId'))->toBe('516930743536');

        return response()->noContent();
    });
});

it('rejects an empty portal ID', function (): void {
    expect(fn (): Response => app(ResolveHubSpotTenant::class)->handle(
        portalRequest(''),
        fn (): Response => response()->noContent(),
    ))->toThrow(HttpException::class, 'Unknown HubSpot portal.');
});

it('rejects a request without portal context', function (): void {
    expect(fn (): Response => app(ResolveHubSpotTenant::class)->handle(
        portalRequest(null),
        fn (): Response => response()->noContent(),
    ))->toThrow(HttpException::class, 'Unknown HubSpot portal.');
});

it('rejects an unknown portal', function (): void {
    expect(fn (): Response => app(ResolveHubSpotTenant::class)->handle(
        portalRequest('99999'),
        fn (): Response => response()->noContent(),
    ))->toThrow(HttpException::class, 'Unknown HubSpot portal.');
});

it('rejects a disabled portal', function (): void {
    expect(fn (): Response => app(ResolveHubSpotTenant::class)->handle(
        portalRequest('67890'),
        fn (): Response => response()->noContent(),
    ))->toThrow(HttpException::class, 'Unknown HubSpot portal.');
});

function portalRequest(string|int|null $portalId): Request
{
    return Request::create('/api/hubspot/warehouse-recommendation-v3', 'POST', [
        'origin' => array_filter([
            'portalId' => $portalId,
        ], static fn (mixed $value): bool => $value !== null),
    ]);
}
