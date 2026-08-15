<?php

declare(strict_types=1);

namespace App\Services\HubSpot\CRM;

use App\Data\HubSpot\Data\HubSpotDealData;
use App\Data\HubSpot\Data\HubSpotLineItemData;
use App\Exceptions\HubSpot\HubSpotCrmNotConfiguredException;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Thin HubSpot CRM REST transport. Owns the base URL, tenant-scoped bearer
 * token, explicit property requests, pagination, and bounded retries. It does
 * not own SKU/quantity/inventory rules — those live in Laravel services.
 */
final readonly class HubSpotCrmClient
{
    public function __construct(
        private string $tenantId,
    ) {}

    /**
     * Read a Deal and its associated Line Item IDs.
     *
     * @param  list<string>  $properties
     */
    public function readDeal(string $dealId, array $properties = ['hs_object_id']): HubSpotDealData
    {
        $response = $this->request()->get(
            '/crm/v3/objects/deals/'.$dealId,
            ['properties' => implode(',', $properties)],
        );

        $this->throwOnFailure($response, 'deal read');

        $lineItemIds = $this->readAssociatedLineItemIds($dealId);
        $dealName = $response->json('properties.dealname');

        return new HubSpotDealData(
            deal_id: $dealId,
            line_item_ids: $lineItemIds,
            deal_name: is_string($dealName) && $dealName !== '' ? $dealName : null,
        );
    }

    /**
     * Read a batch of Line Items by ID, requesting only the configured
     * properties. Returns the raw records so the normalizer can map them.
     *
     * @param  list<string>  $lineItemIds
     * @param  list<string>  $properties
     * @return list<HubSpotLineItemData>
     */
    public function readLineItems(array $lineItemIds, array $properties): array
    {
        if ($lineItemIds === []) {
            return [];
        }

        $response = $this->request()->post('/crm/v3/objects/line_items/batch/read', [
            'inputs'     => array_map(static fn (string $id): array => ['id' => $id], $lineItemIds),
            'properties' => $properties,
        ]);

        $this->throwOnFailure($response, 'line item batch read');

        $results = $response->json('results');

        if (! is_array($results)) {
            throw new HubSpotCrmReadException('HubSpot line item batch read returned no results.');
        }

        $lineItems = [];

        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            if (! is_string($result['id'] ?? null)) {
                continue;
            }

            $rawProperties = $result['properties'] ?? [];

            /** @var array<string, string> $propertiesMap */
            $propertiesMap = is_array($rawProperties)
                ? array_filter($rawProperties, is_string(...))
                : [];

            $lineItems[] = new HubSpotLineItemData(
                line_item_id: $result['id'],
                properties: $propertiesMap,
            );
        }

        return $lineItems;
    }

    public function findNoteByMarker(string $marker): ?string
    {
        $response = $this->request()->post('/crm/v3/objects/notes/search', [
            'filterGroups' => [[
                'filters' => [[
                    'propertyName' => 'hs_note_body',
                    'operator'     => 'CONTAINS_TOKEN',
                    'value'        => $marker,
                ]],
            ]],
            'limit'      => 1,
            'properties' => ['hs_note_body'],
        ]);

        $this->throwOnFailure($response, 'note search');

        $id = $response->json('results.0.id');

        return is_string($id) ? $id : null;
    }

    /** @param  array<string, mixed>  $properties */
    public function createDealNote(string $dealId, array $properties): string
    {
        $associationTypeId = config('hubspot.notes.association_type_id', 214);
        $associationObjectId = ctype_digit($dealId) ? (int) $dealId : $dealId;

        $response = $this->request()->post('/crm/v3/objects/notes', [
            'properties'   => $properties,
            'associations' => [[
                'to'    => ['id' => $associationObjectId],
                'types' => [[
                    'associationCategory' => 'HUBSPOT_DEFINED',
                    'associationTypeId'   => is_int($associationTypeId) ? $associationTypeId : 214,
                ]],
            ]],
        ]);

        $this->throwOnFailure($response, 'note create');

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new HubSpotCrmReadException('HubSpot note create returned no note id.');
        }

        return $id;
    }

    /**
     * @return list<string>
     */
    private function readAssociatedLineItemIds(string $dealId): array
    {
        $ids = [];
        $after = null;

        do {
            $query = ['limit' => 100];

            if ($after !== null) {
                $query['after'] = $after;
            }

            $response = $this->request()->get(
                sprintf('/crm/v3/objects/deals/%s/associations/line_items', $dealId),
                $query,
            );

            $this->throwOnFailure($response, 'deal line item associations read');

            $results = $response->json('results');

            if (is_array($results)) {
                foreach ($results as $result) {
                    if (is_array($result) && is_string($result['id'] ?? null)) {
                        $ids[] = $result['id'];
                    }
                }
            }

            $after = $response->json('paging.next.after');
        } while (is_string($after) && $after !== '');

        return array_values(array_unique($ids));
    }

    private function request(): PendingRequest
    {
        $token = $this->tenantToken();
        $baseUrl = config('hubspot.crm.base_url', 'https://api.hubapi.com');
        $timeout = config('hubspot.crm.timeout', 15);
        $retryTimes = config('hubspot.crm.retry.times', 3);
        $retrySleepMs = config('hubspot.crm.retry.sleep_ms', 100);

        return Http::baseUrl(is_string($baseUrl) ? $baseUrl : 'https://api.hubapi.com')
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(is_int($timeout) ? $timeout : 15)
            ->retry(
                is_int($retryTimes) ? max(1, $retryTimes + 1) : 4,
                is_int($retrySleepMs) ? $retrySleepMs : 100,
                fn (Throwable $throwable): bool => $this->shouldRetry($throwable),
                false,
            );
    }

    private function tenantToken(): string
    {
        $serviceKeys = config('hubspot.crm.service_keys', []);
        $token = is_array($serviceKeys) ? ($serviceKeys[$this->tenantId] ?? null) : null;

        if (! is_string($token) || $token === '') {
            throw new HubSpotCrmNotConfiguredException(
                sprintf('No HubSpot CRM connection is configured for tenant [%s].', $this->tenantId),
            );
        }

        return $token;
    }

    private function shouldRetry(Throwable|Response $throwable): bool
    {
        if ($throwable instanceof ConnectionException) {
            return true;
        }

        if ($throwable instanceof RequestException) {
            $response = $throwable->response;
            if ($response->status() >= 500) {
                return true;
            }

            return $response->status() === 429;
        }

        if (! $throwable instanceof Response) {
            return false;
        }

        if ($throwable->status() >= 500) {
            return true;
        }

        return $throwable->status() === 429;
    }

    private function throwOnFailure(Response $response, string $operation): void
    {
        if ($response->successful()) {
            return;
        }

        $providerMessage = $response->json('message');
        $providerCategory = $response->json('category');
        $correlationId = $response->json('correlationId');
        $diagnostics = [];

        if (is_string($providerCategory) && $providerCategory !== '') {
            $diagnostics[] = 'category='.$providerCategory;
        }

        if (is_string($providerMessage) && $providerMessage !== '') {
            $diagnostics[] = 'message='.mb_substr($providerMessage, 0, 240);
        }

        if (is_string($correlationId) && $correlationId !== '') {
            $diagnostics[] = 'correlation_id='.$correlationId;
        }

        throw new HubSpotCrmReadException(
            sprintf(
                'HubSpot CRM %s failed with status %d%s.',
                $operation,
                $response->status(),
                $diagnostics === [] ? '' : ' ('.implode('; ', $diagnostics).')',
            ),
        );
    }
}
