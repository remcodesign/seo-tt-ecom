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

        if (! is_string($portalId) || $portalId === '' || ! is_array($portalTenants)) {
            abort(403, 'Unknown HubSpot portal.');
        }

        $portalConfig = $portalTenants[$portalId] ?? null;

        if (! is_array($portalConfig)
            || ($portalConfig['enabled'] ?? false) !== true
            || ! is_string($portalConfig['tenant_id'] ?? null)
            || $portalConfig['tenant_id'] === '') {
            abort(403, 'Unknown HubSpot portal.');
        }

        $request->attributes->set('hubspot_portal_id', $portalId);
        $request->attributes->set('hubspot_tenant_id', $portalConfig['tenant_id']);

        return $next($request);
    }
}
