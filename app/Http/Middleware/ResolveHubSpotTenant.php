<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveHubSpotTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $portalId = $request->input('origin.portalId');
        $portalTenants = config('hubspot.portal_tenants', []);

        if ((! is_string($portalId) && ! is_int($portalId)) || ! is_array($portalTenants)) {
            abort(403, 'Unknown HubSpot portal.');
        }

        $portalId = (string) $portalId;

        if ($portalId === '') {
            abort(403, 'Unknown HubSpot portal.');
        }

        $portalConfig = $portalTenants[$portalId] ?? null;

        if (! is_array($portalConfig)
            || ($portalConfig['enabled'] ?? false) !== true
            || ! is_string($portalConfig['tenant_id'] ?? null)
            || $portalConfig['tenant_id'] === '') {
            abort(403, 'Unknown HubSpot portal.');
        }

        $request->merge([
            'origin' => $this->normalizeNumericIds(
                collect((array) $request->input('origin'))
                    ->put('portalId', $portalId)
                    ->all(),
                ['actionDefinitionId', 'actionDefinitionVersion'],
            ),
            'context' => $this->normalizeNumericIds(
                (array) $request->input('context'),
                ['workflowId'],
            ),
            'object' => $this->normalizeNumericIds(
                (array) $request->input('object'),
                ['objectId'],
            ),
        ]);
        $request->attributes->set('hubspot_portal_id', $portalId);
        $request->attributes->set('hubspot_tenant_id', $portalConfig['tenant_id']);

        return $next($request);
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @param  list<string>  $keys
     * @return array<int|string, mixed>
     */
    private function normalizeNumericIds(array $values, array $keys): array
    {
        return collect($values)
            ->map(function (mixed $value, int|string $key) use ($keys): mixed {
                return in_array($key, $keys, true) && is_int($value)
                    ? (string) $value
                    : $value;
            })
            ->all();
    }
}
