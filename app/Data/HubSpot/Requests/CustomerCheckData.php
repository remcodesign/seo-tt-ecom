<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CustomerCheckData extends Data
{
    public function __construct(
        #[Required, StringType, Email, Max(255)]
        public string $email,
    ) {}
}
