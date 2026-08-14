<?php

declare(strict_types=1);

namespace App\Exceptions\HubSpot;

use RuntimeException;

/**
 * Base exception for HubSpot CRM transport failures.
 */
class HubSpotCrmException extends RuntimeException {}
