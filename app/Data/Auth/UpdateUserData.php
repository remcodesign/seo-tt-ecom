<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\RoleLabel;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum as EnumValidation;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UpdateUserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,

        #[Required, Email, Max(255), Unique('users', 'email', ignore: new RouteParameterReference('user', 'id'), ignoreColumn: 'id')]
        public string $email,

        #[Required, EnumValidation(RoleLabel::class)]
        public RoleLabel $role_label,

        #[Nullable, StringType, Min(8), Confirmed]
        public ?string $password = null,
    ) {}
}
