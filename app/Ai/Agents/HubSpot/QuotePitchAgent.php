<?php

declare(strict_types=1);

namespace App\Ai\Agents\HubSpot;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

final class QuotePitchAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Write a concise professional quote pitch in plain text. Use only supplied facts. Do not invent customer details, prices, guarantees, company name, or HTML. Keep it under 120 words.';
    }
}
