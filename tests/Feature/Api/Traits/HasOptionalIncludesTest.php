<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tests\Feature\Api\Traits\Stubs\HasOptionalIncludesDataStub;
use Tests\Feature\Api\Traits\Stubs\HasOptionalIncludesDefaultAllowedIncludesStub;
use Tests\Feature\Api\Traits\Stubs\HasOptionalIncludesLoadMissingModel;
use Tests\Feature\Api\Traits\Stubs\HasOptionalIncludesTestStub;

beforeEach(function (): void {
    app()['request'] = Request::create('/', 'GET');
});

describe('HasOptionalIncludes', function (): void {
    it('filters requested includes against allowed list', function (): void {
        $request = Request::create('/?include=user,invalid,post', 'GET');
        app()['request'] = $request;

        $stub = new HasOptionalIncludesTestStub;

        $includes = $stub->requestIncludedRelationsPublic();

        expect($includes)->toBe(['user', 'post']);
    });

    it('returns empty when no include query is present', function (): void {
        $request = Request::create('/', 'GET');
        app()['request'] = $request;

        $stub = new HasOptionalIncludesTestStub;

        $includes = $stub->requestIncludedRelationsPublic();

        expect($includes)->toBe([]);
    });

    it('returns empty when no allowed includes are defined', function (): void {
        $request = Request::create('/?include=user,post', 'GET');
        app()['request'] = $request;

        // Create a stub with no allowed includes
        $stub = new HasOptionalIncludesTestStub([]);

        $includes = $stub->requestIncludedRelationsPublic();

        expect($includes)->toBe([]);
    });

    // default allowed includes implementation is to return an empty array, so this test ensures that behavior is preserved
    it('uses the default allowed includes implementation when none are overridden', function (): void {
        $request = Request::create('/?include=user,post', 'GET');
        app()['request'] = $request;

        $stub = new HasOptionalIncludesDefaultAllowedIncludesStub;

        $includes = $stub->requestIncludedRelationsPublic();

        expect($includes)->toBe([]);
    });

    it('returns the model unchanged when includes are empty', function (): void {
        $model = new HasOptionalIncludesLoadMissingModel;
        $stub = new HasOptionalIncludesTestStub;

        $result = $stub->loadIncludesPublic($model, []);

        expect($result)->toBe($model);
        expect($model->loadedIncludes)->toBe([]);
    });

    it('loads missing relations when includes are supplied', function (): void {
        $model = new HasOptionalIncludesLoadMissingModel;
        $stub = new HasOptionalIncludesTestStub;

        $result = $stub->loadIncludesPublic($model, ['user']);

        expect($result)->toBe($model);
        expect($model->loadedIncludes)->toBe(['user']);
    });

    it('does not apply includes when the list is empty', function (): void {
        $data = new HasOptionalIncludesDataStub;
        $stub = new HasOptionalIncludesTestStub;

        $stub->applyIncludesPublic($data, []);

        expect($data->included)->toBe([]);
    });

    it('applies includes when the list is supplied', function (): void {
        $data = new HasOptionalIncludesDataStub;
        $stub = new HasOptionalIncludesTestStub;

        $stub->applyIncludesPublic($data, ['user', 'post']);

        expect($data->included)->toBe(['user', 'post']);
    });

    it('resolves optional includes and loads them on the model', function (): void {
        $request = Request::create('/?include=user', 'GET');
        app()['request'] = $request;

        $model = new HasOptionalIncludesLoadMissingModel;
        $stub = new HasOptionalIncludesTestStub(['user']);

        [$result, $includes] = $stub->resolveOptionalIncludesPublic($model);

        expect($result)->toBe($model);
        expect($includes)->toBe(['user']);
        expect($model->loadedIncludes)->toBe(['user']);
    });
});
