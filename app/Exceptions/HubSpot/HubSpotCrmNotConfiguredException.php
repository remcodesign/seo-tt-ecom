<?php

declare(strict_types=1);

namespace App\Exceptions\HubSpot;

/**
 * Raised when the tenant has no authorized HubSpot CRM connection configured.
 */
class HubSpotCrmNotConfiguredException extends HubSpotCrmException {}
