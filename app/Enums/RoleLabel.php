<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleLabel: string
{
    case guest = 'guest';
    case user = 'user';
    case writer = 'writer';
    case admin = 'admin';
}
