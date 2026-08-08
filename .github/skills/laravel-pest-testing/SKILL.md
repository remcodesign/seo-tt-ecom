---
name: laravel-pest-testing
description: "Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code."
license: MIT
metadata:
  author: laravel
---

# Pest Testing 4

## Documentation

Use `search-docs` for detailed Pest 4 patterns and documentation.

## Basic Usage

### Creating Tests

All tests must be written using Pest. Use `php artisan make:test --pest {name}`.

The `{name}` argument should include only the path and test name, but should not include the test suite.
- Incorrect: `php artisan make:test --pest Feature/SomeFeatureTest` will generate `tests/Feature/Feature/SomeFeatureTest.php`
- Correct: `php artisan make:test --pest SomeControllerTest` will generate `tests/Feature/SomeControllerTest.php`
- Incorrect: `php artisan make:test --pest --unit Unit/SomeServiceTest` will generate `tests/Unit/Unit/SomeServiceTest.php`
- Correct: `php artisan make:test --pest --unit SomeServiceTest` will generate `tests/Unit/SomeServiceTest.php`

### Test Organization

- Unit/Feature tests: `tests/Feature` and `tests/Unit` directories.
- Browser tests: `tests/Browser/` directory.
- Do NOT remove tests without approval - these are core application code.

### Test Boundaries: Test Real Code

Tests must prove behavior at the production boundary they name. Always invoke the real class, service, Livewire component, controller, job, or domain function under test. Do not write tests whose only subject is a hand-written mock, fixture, anonymous implementation, or local helper declared inside the test file. If the test would still pass after deleting or breaking the production implementation, it is not testing the application and should be removed or rewritten.

Mocks, fakes, stubs, and fixtures are test tools, not production behavior. Use them only to isolate a dependency outside the boundary under test, such as:

- an external HTTP API or provider;
- an AI model or agent;
- a queue, notification, mail transport, filesystem, clock, or other nondeterministic infrastructure;
- an authenticated user, factory record, or external input required to reach the real code path.

When using a test double, assert the real code's interaction with it. Verify exact request URLs, methods, payloads, configuration, dispatched jobs, notifications, prompts, selected models, persisted state, returned DTOs, and user-visible output as applicable. Do not merely assert that a mock returns the value it was programmed to return, and do not test a type or interface by exercising a locally defined implementation.

Every external boundary test must cover the successful contract and relevant failure paths, including validation failures, authorization failures, missing or empty data, malformed responses, non-success HTTP responses, dependency exceptions, and deterministic fallback behavior. Tests must never call production APIs, live AI providers, production databases, or real third-party accounts. Use framework fakes for those boundaries and keep live-account checks in a separately controlled integration or manual workflow.

Prefer focused tests that distinguish a meaningful regression over broad tests that duplicate lower-level assertions. Feature tests should exercise the complete application path relevant to the feature; unit tests should isolate pure deterministic logic. Do not duplicate a test merely because a fixture has a particular value: add coverage only when it verifies a distinct behavior, contract, state transition, or failure mode.

### Basic Test Structure

Pest supports both `test()` and `it()` functions. Before writing new tests, check existing test files in the same directory to match the project's convention. Use `test()` if existing tests use `test()`, or `it()` if they use `it()`.

<!-- Basic Pest Test Example -->
```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

### Running Tests

- Run minimal tests with filter before finalizing: `php artisan test --compact --filter=testName`.
- Run all tests: `php artisan test --compact`.
- Run file: `php artisan test --compact tests/Feature/ExampleTest.php`.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

<!-- Pest Response Assertion -->
```php
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
```

| Use | Instead of |
|-----|------------|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

## Laravel Test Coverage

### Model Tests

Cover the model's configuration, relationships, and database constraints:

- Factory data is valid, creates the expected model, and persists.
- Critical casts, mass-assignment guardrails, and hidden fields behave correctly.
- `BelongsTo`, `HasMany`, many-to-many, and pivot relationships return the expected records and types.
- Required foreign keys, uniqueness rules, and cascade behavior fail or persist as intended.

### Service Tests

Test business behavior rather than duplicating model schema tests:

- Successful creation, ownership, update, delete, and partial-update behavior.
- Authorization failures for non-owners, unauthenticated users, and invalid actors.
- Generated values, collision handling, state transitions, logging, notifications, and cache effects.
- Pagination, filtering, optional includes, eager loading, and query contracts where the service owns them.

### API Controller Tests

Verify the complete HTTP lifecycle without repeating service implementation details:

- `index` returns the expected collection, pagination metadata, ordering, and safe fallback for invalid query options.
- `show`, `store`, `update`, and `destroy` return the correct response shape and persistence result.
- Protected routes reject unauthenticated users and ownership violations with the appropriate response.
- Invalid or missing input returns validation errors using JSON request helpers and specific assertions.

Use `getJson()`, `postJson()`, `putJson()`, and `deleteJson()` for JSON APIs. Authenticate with the project's supported test helper and use factories for database state.

### Livewire Tests

Use `Livewire::test()` against the real component. Assert authorization redirects, validation errors, state changes, dispatched events, rendered user-visible output, and relevant action side effects. Use `assertSet()` for component state, `assertDispatched()` for events, and `assertSeeHtml()` when asserting raw markup or Livewire attributes. Do not call lifecycle hooks directly to simulate user interaction.

### Browser Tests

Use browser tests for complete user workflows and frontend integration. Prefer stable `data-test` selectors over styling or translated visible text, assert no JavaScript errors, and cover loading, empty, validation, authorization, success, and failure states that matter to the workflow.

## Test Verification

Run the narrowest affected test file or filter first, then the broader relevant suite. For PHP changes, run the repository's formatter and configured static analysis after behavior tests. A test change is not complete until the executable checks pass, or any unavailable check and its reason are reported explicitly.

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

<!-- Pest Dataset Example -->
```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

## Pest 4 Features

| Feature | Purpose |
|---------|---------|
| Browser Testing | Full integration tests in real browsers |
| Smoke Testing | Validate multiple pages quickly |
| Visual Regression | Compare screenshots for visual changes |
| Test Sharding | Parallel CI runs |
| Architecture Testing | Enforce code conventions |

### Browser Test Example

Browser tests run in real browsers for full integration testing:

- Browser tests live in `tests/Browser/`.
- Use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories.
- Use `RefreshDatabase` for clean state per test.
- Interact with page: click, type, scroll, select, submit, drag-and-drop, touch gestures.
- Test on multiple browsers (Chrome, Firefox, Safari) if requested.
- Test on different devices/viewports (iPhone 14 Pro, tablets) if requested.
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging.

<!-- Pest Browser Test Example -->
```php
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in');

    $page->assertSee('Sign In')
        ->assertNoJavaScriptErrors()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!');

    Notification::assertSent(ResetPassword::class);
});
```

### Smoke Testing

Quickly validate multiple pages have no JavaScript errors:

<!-- Pest Smoke Testing Example -->
```php
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavaScriptErrors()->assertNoConsoleLogs();
```

### Visual Regression Testing

Capture and compare screenshots to detect visual changes.

### Test Sharding

Split tests across parallel processes for faster CI runs.

### Architecture Testing

Pest 4 includes architecture testing (from Pest 3):

<!-- Architecture Test Example -->
```php
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');
```

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
- Forgetting `assertNoJavaScriptErrors()` in browser tests
- Prefixing `Feature/` or `Unit/` in `{name}` when using `make:test`
