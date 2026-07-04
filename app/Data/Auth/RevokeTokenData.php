<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class RevokeTokenData extends Data
{
    public function __construct(public string $message) {}
}
