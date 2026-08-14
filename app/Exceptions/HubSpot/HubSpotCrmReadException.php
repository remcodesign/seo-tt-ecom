<?php

declare(strict_types=1);

namespace App\Exceptions\HubSpot;

/**
 * Raised when a HubSpot CRM read fails (network, 5xx, rate limit, or 4xx).
 */
class HubSpotCrmReadException extends HubSpotCrmException {}
