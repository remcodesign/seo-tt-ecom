<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use App\Http\Controllers\Api\Traits\HasOrderBy;

class HasOrderByTestStub
{
    use HasOrderBy;

    public function __construct(
        private array $allowedFields = [],
    ) {}

    public function allowedOrderByFieldsPublic(): array
    {
        return $this->allowedOrderByFields();
    }

    protected function allowedOrderByFields(): array
    {
        return $this->allowedFields;
    }

    public function getOrderByPublic(string $default = 'created_at', string $defaultDirection = 'desc'): array
    {
        return $this->getOrderBy($default, $defaultDirection);
    }
}
