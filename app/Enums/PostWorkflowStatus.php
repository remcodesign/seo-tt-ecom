<?php

declare(strict_types=1);

namespace App\Enums;

enum PostWorkflowStatus: string
{
    case discovered = 'discovered'; // found new image
    case proxy_created = 'proxy_created';  // converted to local proxy
    case uploaded = 'uploaded'; // uploaded to GCS
    case described = 'described'; // description + title + tags (csv string) generated via (local) AI
    case embedded = 'embedded'; // embedding generated via (local) AI
    case completed = 'completed'; // all steps completed, including category via (local) AI and other metadata
}
