<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tests\Feature\Api\Traits\Stubs\HasOrderByDefaultAllowedFieldsStub;
use Tests\Feature\Api\Traits\Stubs\HasOrderByTestStub;

// Uses the same pattern as HasOptionalIncludesTest — a plain PHP class with the trait
// so we can test the trait methods in isolation. The request() helper needs the
// request to be set in the container, which requires a feature-test environment.

beforeEach(function (): void {
    app()['request'] = Request::create('/', 'GET');
});

describe('HasOrderBy', function (): void {
    it('returns empty allowed order-by fields by default', function (): void {
        $stub = new HasOrderByDefaultAllowedFieldsStub;

        expect($stub->allowedOrderByFieldsPublic())->toBe([]);
    });

    it('uses custom allowed fields from the consuming class', function (): void {
        $stub = new HasOrderByTestStub(['published_on', 'updated_at']);

        expect($stub->allowedOrderByFieldsPublic())->toBe(['published_on', 'updated_at']);
    });

    it('returns the default column and asc direction when no orderby is given', function (): void {
        app()['request'] = Request::create('/', 'GET');
        $stub = new HasOrderByTestStub(['created_at', 'updated_at']);

        [$column, $direction] = $stub->getOrderByPublic('created_at', 'desc');

        expect($column)->toBe('created_at')
            ->and($direction)->toBe('asc');
    });

    it('parses an asc orderby value', function (): void {
        app()['request'] = Request::create('/?orderby=updated_at', 'GET');
        $stub = new HasOrderByTestStub(['created_at', 'updated_at']);

        [$column, $direction] = $stub->getOrderByPublic('created_at', 'desc');

        expect($column)->toBe('updated_at')
            ->and($direction)->toBe('asc');
    });

    it('parses a desc orderby with _desc suffix', function (): void {
        app()['request'] = Request::create('/?orderby=updated_at_desc', 'GET');
        $stub = new HasOrderByTestStub(['created_at', 'updated_at']);

        [$column, $direction] = $stub->getOrderByPublic('created_at', 'desc');

        expect($column)->toBe('updated_at')
            ->and($direction)->toBe('desc');
    });

    it('falls back to defaults when the column is not in the allowed list', function (): void {
        app()['request'] = Request::create('/?orderby=invalid_column', 'GET');
        $stub = new HasOrderByTestStub(['created_at', 'updated_at']);

        [$column, $direction] = $stub->getOrderByPublic('created_at', 'desc');

        expect($column)->toBe('created_at')
            ->and($direction)->toBe('desc');
    });
});
