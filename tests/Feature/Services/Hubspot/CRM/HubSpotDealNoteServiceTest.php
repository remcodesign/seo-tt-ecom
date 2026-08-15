<?php

declare(strict_types=1);

use App\Services\HubSpot\CRM\HubSpotCrmClient;
use App\Services\HubSpot\CRM\HubSpotDealNoteService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'hubspot.crm.base_url'       => 'https://api.hubapi.com',
        'hubspot.crm.service_keys'   => ['tenant-test' => 'service-key'],
        'hubspot.crm.retry.times'    => 0,
        'hubspot.crm.retry.sleep_ms' => 0,
    ]);
});

it('returns the existing note id without creating a duplicate note', function (): void {
    $marker = '[warehouse-recommendation-task:task-001]';

    Http::fake([
        'api.hubapi.com/crm/v3/objects/notes/search' => Http::response([
            'results' => [['id' => 'note-existing']],
        ]),
        'api.hubapi.com/crm/v3/objects/notes' => Http::response(['id' => 'note-created'], 201),
    ]);

    $service = new HubSpotDealNoteService(new HubSpotCrmClient('tenant-test'));

    $noteId = $service->createOnce('500005', $marker, [
        'hs_note_body' => 'Warehouse recommendation result.',
    ]);

    expect($noteId)->toBe('note-existing');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => str_ends_with((string) $request->url(), '/crm/v3/objects/notes/search')
        && $request['filterGroups'][0]['filters'][0]['value'] === $marker);
});
