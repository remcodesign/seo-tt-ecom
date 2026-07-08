<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\RoleLabel;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserDataResponse extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public RoleLabel $role_label,
    ) {}
}
