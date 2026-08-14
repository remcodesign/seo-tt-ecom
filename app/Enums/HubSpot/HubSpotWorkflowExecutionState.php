<?php

declare(strict_types=1);

namespace App\Enums\HubSpot;

enum HubSpotWorkflowExecutionState: string
{
    case Success = 'SUCCESS';
    case FailContinue = 'FAIL_CONTINUE';
}
