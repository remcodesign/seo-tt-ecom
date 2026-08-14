<?php

declare(strict_types=1);

namespace App\Exceptions\HubSpot;

/**
 * Raised when normalized Line Item data is missing, duplicated, or invalid.
 */
class LineItemDataInvalidException extends HubSpotCrmException {}
