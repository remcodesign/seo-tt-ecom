<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class QuotePitchData extends Data
{
    public function __construct(
        #[Required, StringType, Max(160)]
        public string $deal_name,

        #[Numeric, Min(0)]
        public ?float $deal_amount,

        #[Required, StringType, Email, Max(255)]
        public string $customer_email,

        #[Required, IntegerType, Min(0), Max(100)]
        public int $allowed_discount,
    ) {}
}
