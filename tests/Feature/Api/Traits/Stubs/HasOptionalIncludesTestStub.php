<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use App\Http\Controllers\Api\Traits\HasOptionalIncludes;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class HasOptionalIncludesTestStub
{
    use HasOptionalIncludes;

    public function __construct(
        private array $allowedIncludes = ['user', 'post'],
    ) {}

    protected function allowedIncludes(): array
    {
        return $this->allowedIncludes;
    }

    public function requestIncludedRelationsPublic(): array
    {
        return $this->requestIncludedRelations();
    }

    public function loadIncludesPublic(Model $model, array $includes): Model
    {
        return $this->loadIncludes($model, $includes);
    }

    public function applyIncludesPublic(Data $data, array $includes): void
    {
        $this->applyIncludes($data, $includes);
    }

    public function resolveOptionalIncludesPublic(Model $model): array
    {
        return $this->resolveOptionalIncludes($model);
    }
}
