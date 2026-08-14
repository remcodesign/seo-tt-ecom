<?php

declare(strict_types=1);

namespace App\Services\HubSpot\CRM;

final readonly class HubSpotDealNoteService
{
    public function __construct(private HubSpotCrmClient $hubSpotCrmClient) {}

    /** @param  array<string, mixed>  $properties */
    public function createOnce(string $dealId, string $marker, array $properties): string
    {
        $existingNoteId = $this->hubSpotCrmClient->findNoteByMarker($marker);

        if ($existingNoteId !== null) {
            return $existingNoteId;
        }

        $body = $properties['hs_note_body'] ?? '';
        $body = is_string($body) ? $body : '';

        return $this->hubSpotCrmClient->createDealNote($dealId, array_merge($properties, [
            'hs_note_body' => $marker."\n".$body,
        ]));
    }
}
