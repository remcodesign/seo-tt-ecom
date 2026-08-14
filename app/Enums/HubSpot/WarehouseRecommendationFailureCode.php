<?php

declare(strict_types=1);

namespace App\Enums\HubSpot;

enum WarehouseRecommendationFailureCode: string
{
    case TaskExpired = 'TASK_EXPIRED';
    case LineItemDataInvalid = 'LINE_ITEM_DATA_INVALID';
    case CrmReadFailed = 'CRM_READ_FAILED';
    case AiResultInvalid = 'AI_RESULT_INVALID';
    case WorkflowFailed = 'WORKFLOW_FAILED';
}
