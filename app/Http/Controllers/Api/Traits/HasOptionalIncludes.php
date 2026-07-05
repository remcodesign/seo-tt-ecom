<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

trait HasOptionalIncludes
{
    /**
     * @return non-falsy-string[]
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    /**
     * @return non-falsy-string[]
     */
    protected function requestIncludedRelations(string $parameter = 'include'): array
    {
        $raw = request()->query($parameter);

        if ($raw === null) {
            return [];
        }

        // Convert the raw input into an array of strings, split by commas, and filter out any empty values
        return collect(Arr::wrap($raw))
            ->flatMap(fn (mixed $value): array => is_string($value) ? explode(',', $value) : [])
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->filter(fn (string $value): bool => in_array($value, $this->allowedIncludes(), true))
            ->values()
            ->all();
    }

    /**
     * @param  string[]  $includes
     */
    protected function loadIncludes(Model $model, array $includes): Model
    {
        if ($includes === []) {
            return $model;
        }

        return $model->loadMissing($includes);
    }

    /**
     * @param  string[]  $includes
     */
    protected function applyIncludes(Data $data, array $includes): void
    {
        if ($includes !== []) {
            $data->include(...$includes);
        }
    }
}
