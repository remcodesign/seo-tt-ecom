<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use App\Http\Controllers\Api\Traits\HasOrderBy;

class HasOrderByDefaultAllowedFieldsStub
{
    use HasOrderBy;

    public function allowedOrderByFieldsPublic(): array
    {
        return $this->allowedOrderByFields();
    }

    public function getOrderByPublic(string $default = 'created_at', string $defaultDirection = 'desc'): array
    {
        return $this->getOrderBy($default, $defaultDirection);
    }
}
