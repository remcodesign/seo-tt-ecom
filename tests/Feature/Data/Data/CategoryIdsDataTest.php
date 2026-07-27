<?php

declare(strict_types=1);

use App\Data\Data\CategoryIdsData;

it('creates category IDs data and exposes its properties', function (): void {
    $data = new CategoryIdsData(
        category_ids: [1, 2, 3],
    );

    expect($data)->toBeInstanceOf(CategoryIdsData::class)
        ->and($data->category_ids)->toBe([1, 2, 3]);
});

it('handles an empty array of category IDs', function (): void {
    $data = new CategoryIdsData(
        category_ids: [],
    );

    expect($data)->toBeInstanceOf(CategoryIdsData::class)
        ->and($data->category_ids)->toBe([]);
});
