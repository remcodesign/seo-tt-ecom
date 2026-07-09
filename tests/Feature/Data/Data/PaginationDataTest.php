<?php

declare(strict_types=1);

use App\Data\Data\PaginatedResponseData;
use App\Data\Data\PaginationLinkData;
use App\Data\Data\PaginationMetaData;

it('creates pagination link data and exposes its properties', function (): void {
    $link = new PaginationLinkData(
        url: 'https://example.com?page=2',
        label: '2',
        page: 2,
        active: false,
    );

    expect($link)->toBeInstanceOf(PaginationLinkData::class)
        ->and($link->url)->toBe('https://example.com?page=2')
        ->and($link->label)->toBe('2')
        ->and($link->page)->toBe(2)
        ->and($link->active)->toBeFalse();
});

it('creates pagination meta data and converts it to an array', function (): void {
    $meta = new PaginationMetaData(
        current_page: 1,
        first_page_url: 'https://example.com?page=1',
        from: 1,
        last_page: 10,
        last_page_url: 'https://example.com?page=10',
        next_page_url: 'https://example.com?page=2',
        path: 'https://example.com',
        per_page: 15,
        prev_page_url: null,
        to: 15,
        total: 150,
    );

    expect($meta)->toBeInstanceOf(PaginationMetaData::class)
        ->and($meta->current_page)->toBe(1)
        ->and($meta->first_page_url)->toBe('https://example.com?page=1')
        ->and($meta->from)->toBe(1)
        ->and($meta->last_page)->toBe(10)
        ->and($meta->last_page_url)->toBe('https://example.com?page=10')
        ->and($meta->next_page_url)->toBe('https://example.com?page=2')
        ->and($meta->path)->toBe('https://example.com')
        ->and($meta->per_page)->toBe(15)
        ->and($meta->prev_page_url)->toBeNull()
        ->and($meta->to)->toBe(15)
        ->and($meta->total)->toBe(150)
        ->and($meta->toArray())->toBe([
            'current_page' => 1,
            'first_page_url' => 'https://example.com?page=1',
            'from' => 1,
            'last_page' => 10,
            'last_page_url' => 'https://example.com?page=10',
            'next_page_url' => 'https://example.com?page=2',
            'path' => 'https://example.com',
            'per_page' => 15,
            'prev_page_url' => null,
            'to' => 15,
            'total' => 150,
        ]);
});

it('allows nullable pagination URLs in meta data', function (): void {
    $meta = new PaginationMetaData(
        current_page: 3,
        first_page_url: 'https://example.com?page=1',
        from: 41,
        last_page: 5,
        last_page_url: 'https://example.com?page=5',
        next_page_url: null,
        path: 'https://example.com',
        per_page: 10,
        prev_page_url: 'https://example.com?page=2',
        to: 50,
        total: 50,
    );

    expect($meta->next_page_url)->toBeNull()
        ->and($meta->toArray()['next_page_url'])->toBeNull();
});

it('serializes an empty paginated response with no items or links', function (): void {
    $meta = new PaginationMetaData(
        current_page: 1,
        first_page_url: 'https://example.com?page=1',
        from: null,
        last_page: 1,
        last_page_url: 'https://example.com?page=1',
        next_page_url: null,
        path: 'https://example.com',
        per_page: 10,
        prev_page_url: null,
        to: null,
        total: 0,
    );

    $paginatedResponse = new PaginatedResponseData(
        data: [],
        links: [],
        meta: $meta,
    );

    expect($paginatedResponse->data)->toBe([])
        ->and($paginatedResponse->links)->toBe([])
        ->and($paginatedResponse->toArray())->toBe([
            'data' => [],
            'links' => [],
            'meta' => [
                'current_page' => 1,
                'first_page_url' => 'https://example.com?page=1',
                'from' => null,
                'last_page' => 1,
                'last_page_url' => 'https://example.com?page=1',
                'next_page_url' => null,
                'path' => 'https://example.com',
                'per_page' => 10,
                'prev_page_url' => null,
                'to' => null,
                'total' => 0,
            ],
        ]);
});

it('creates a paginated response with data, links, and metadata', function (): void {
    $meta = new PaginationMetaData(
        current_page: 1,
        first_page_url: 'https://example.com?page=1',
        from: 1,
        last_page: 3,
        last_page_url: 'https://example.com?page=3',
        next_page_url: 'https://example.com?page=2',
        path: 'https://example.com',
        per_page: 2,
        prev_page_url: null,
        to: 2,
        total: 6,
    );

    $links = [
        new PaginationLinkData(url: 'https://example.com?page=1', label: '1', page: 1, active: true),
        new PaginationLinkData(url: 'https://example.com?page=2', label: '2', page: 2, active: false),
    ];

    $data = [
        ['id' => 1, 'name' => 'First item'],
        ['id' => 2, 'name' => 'Second item'],
    ];

    $paginatedResponse = new PaginatedResponseData(
        data: $data,
        links: $links,
        meta: $meta,
    );

    expect($paginatedResponse)->toBeInstanceOf(PaginatedResponseData::class)
        ->and($paginatedResponse->data)->toBe($data)
        ->and($paginatedResponse->links)->toBe($links)
        ->and($paginatedResponse->meta)->toBe($meta)
        ->and($paginatedResponse->toArray())->toBe([
            'data' => $data,
            'links' => [
                [
                    'url' => 'https://example.com?page=1',
                    'label' => '1',
                    'page' => 1,
                    'active' => true,
                ],
                [
                    'url' => 'https://example.com?page=2',
                    'label' => '2',
                    'page' => 2,
                    'active' => false,
                ],
            ],
            'meta' => [
                'current_page' => 1,
                'first_page_url' => 'https://example.com?page=1',
                'from' => 1,
                'last_page' => 3,
                'last_page_url' => 'https://example.com?page=3',
                'next_page_url' => 'https://example.com?page=2',
                'path' => 'https://example.com',
                'per_page' => 2,
                'prev_page_url' => null,
                'to' => 2,
                'total' => 6,
            ],
        ]);
});
