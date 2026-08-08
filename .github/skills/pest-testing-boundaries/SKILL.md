---
name: pest-testing-boundaries
description: "Use this skill when writing or reviewing Pest tests in Laravel projects for test-boundary design and meaningful coverage. Trigger when deciding whether a test exercises real production code, choosing between feature and unit tests, isolating external dependencies with mocks or fakes, verifying interactions with test doubles, or covering success and failure contracts at an external boundary. Covers production-boundary testing, non-tautological assertions, mock and fake usage, interaction verification, failure-path coverage, and avoiding tests that only exercise local fixtures or implementations. Do not use for general Pest syntax, factories, seeders, migrations, controllers, models, or non-test PHP code."
---

## Test Boundaries: Test Real Code

Tests must prove behavior at the production boundary they name. Always invoke the real class, service, Livewire component, controller, job, or domain function under test. Do not write tests whose only subject is a hand-written mock, fixture, anonymous implementation, or local helper declared inside the test file. If the test would still pass after deleting or breaking the production implementation, it is not testing the application and should be removed or rewritten.

Mocks, fakes, stubs, and fixtures are test tools, not production behavior. Use them only to isolate a dependency outside the boundary under test, such as:

- an external HTTP API or provider;
- an AI model or agent;
- a queue, notification, mail transport, filesystem, clock, or other nondeterministic infrastructure;
- an authenticated user, factory record, or external input required to reach the real code path.

When using a test double, assert the real code's interaction with it. Verify exact request URLs, methods, payloads, configuration, dispatched jobs, notifications, prompts, selected models, persisted state, returned DTOs, and user-visible output as applicable. Do not merely assert that a mock returns the value it was programmed to return, and do not test a type or interface by exercising a locally defined implementation.

Every external boundary test must cover the successful contract and relevant failure paths, including validation failures, authorization failures, missing or empty data, malformed responses, non-success HTTP responses, dependency exceptions, and deterministic fallback behavior. Tests must never call production APIs, live AI providers, production databases, or real third-party accounts. Use framework fakes for those boundaries and keep live-account checks in a separately controlled integration or manual workflow.

Prefer focused tests that distinguish a meaningful regression over broad tests that duplicate lower-level assertions. Feature tests should exercise the complete application path relevant to the feature; unit tests should isolate pure deterministic logic. Do not duplicate a test merely because a fixture has a particular value: add coverage only when it verifies a distinct behavior, contract, state transition, or failure mode.

## Laravel Test Coverage

### Model Tests

Cover the model's configuration, relationships, and database constraints:

- Factory data is valid, creates the expected model, and persists.
- Critical casts, mass-assignment guardrails, and hidden fields behave correctly.
- `BelongsTo`, `HasMany`, `many-to-many`, and `pivot` relationships return the expected records and types.
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

### Browser Test Conventions

Use browser tests for complete user workflows and frontend integration. Prefer stable `data-test` selectors over styling or translated visible text, assert no JavaScript errors, and cover loading, empty, validation, authorization, success, and failure states that matter to the workflow.

## Test Verification

Run the narrowest affected test file or filter first, then the broader relevant suite. For PHP changes, run the repository's formatter and configured static analysis after behavior tests. A test change is not complete until the executable checks pass, or any unavailable check and its reason are reported explicitly.
