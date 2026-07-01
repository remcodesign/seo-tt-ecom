---
description: Project-specific workspace instructions for Laravel application code and review.
applyTo: '**/*'
---

Use these guidelines when writing or reviewing code in this workspace:

- This is a Laravel 13 application on PHP 8.4. Prefer Laravel conventions and the existing app structure.
- Use Laravel Boost tools and application tooling when available for inspection, migrations, and package/version context. 
- After changing PHP code, run `ddev composer format` to apply Pint/Rector and verify static analysis with PHPStan/Larastan.
- Maintain `phpstan` compatibility at the configured level, including generic relation return annotations. Prefer concise relation docblocks such as `@return HasMany<Post, $this>`.
- When creating models, migrations, or factories, keep `app/Models`, `database/migrations`, and `database/factories` aligned with current repository conventions.
- Prefer `php artisan make:*` scaffolding patterns only when they fit the existing project styles and toolset.
- Read current file contents before editing because files may change between requests.
- Do not add new dependencies unless explicitly requested.
- Use Pest for tests and verify changes with the workspace test runner when appropriate.

---

## Model & Factory Testing Standards
When creating or modifying Eloquent models, you must write comprehensive Pest tests to verify the database and hydration layer before moving to services or controllers.

### Execution Style
- Write tests using **Pest PHP** functional syntax (`it()`, `expect()`).
- Group tests for the same model logically using `describe('ModelName', function () { ... })` blocks for structure and scannability.
- Place integration/database-interacting tests inside `tests/Feature/Models/` and pure isolated logic in `tests/Unit/`.
- Always include `uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);` at the top of files interacting with the schema.

---

## Blueprint: Strict Minimal Required Test Scope

Every model file must be accompanied by a Pest test file covering these three distinct `describe()` categories. You must implement at least the **absolute minimum** requirements listed below:

also consult the `.github/skills/pest-testing/SKILL.md` for additional guidance on Pest testing best practices.

> model tests

### 1. Configuration & Data Integrity
*Focus: Model hydration and serialization.*
- **[MINIMAL] Factory Validation:** Assert that the model's factory generates valid data, instantiates the correct class, and successfully persists to the database.
- **[MINIMAL] Cast Verification:** Explicitly test custom/critical attribute casting (e.g., datetimes, booleans, arrays, enums). This is the most vital configuration to test as errors break the hydration layer.
- **Mass Assignment & Security:** Verify `fillable` guardrails and `hidden` attributes (ensuring sensitive fields do not leak during serialization like `toArray()` or `toJson()`).

### 2. Relationship Integrity (Happy Path)
*Focus: Eloquent relationship execution and hydration.*
- **[MINIMAL] BelongsTo:** Assert that a child model correctly returns an instance of its parent model, matching the exact keys.
- **[MINIMAL] HasMany / Many-to-Many:** If a model can own multiple child records, test the relationship by creating multiple entities (e.g., count of 3) and assert the collection count and instance type match.
- **Pivots:** Verify intermediate table connections, including custom pivot table attributes or specialized pivot casting if applicable.

### 3. Database Constraints & Rules (Unhappy Path)
*Focus: Ensuring the database enforces constraints independently of application logic.*
- **[MINIMAL] Strict Foreign Keys:** Ensure database-level constraints work as intended. Assert that a `QueryException` is thrown when trying to save a model without its mandatory foreign parent relation (e.g., a child record cannot exist as an orphan without its parent ID).
- **Cascade Operations:** Assert that `cascadeOnDelete()` parameters function correctly by deleting a parent model and verifying the automatic removal of child records from the database.

> service tests

### Service-layer scope
*Focus: test service behavior, not low-level model schema.*
- Prefer testing nullable fields, casting, hydration, and basic persistence at the model layer. If a service only passes values through to Eloquent without transforming them or adding side effects, avoid duplicating those low-level assertions in service tests.
- Thin pass-through services should not re-test model serialization or persistence details that are already covered by the model layer.
- Use service tests for business contracts, authorization, derived values, side effects, and any case where `null` or other input values change the service's behavior.

### 1. Happy Path — Creation
*Focus: Verify the service correctly creates the resource and returns the expected model.*
- **[MINIMAL] Successful Creation:** Assert that `create()` returns the correct model instance, persists to the database, and sets the correct attributes (including auto-generated values like slugs).
- **[MINIMAL] Ownership/Link:** Verify the created resource is correctly associated with the authenticated user or parent model (e.g., `$post->user_id` matches the creator).

### 2. Happy Path — Update & Delete
*Focus: Verify mutation methods work correctly for authorized users.*
- **[MINIMAL] Update Success:** Assert that `update()` modifies the intended attributes in the database without unintended side effects.
- **[MINIMAL] Delete Success:** Assert that `delete()` removes the record from the database.
- **Partial Updates:** Verify that updating a subset of attributes leaves the rest untouched (e.g., updating only `body` should not change `title` or `slug`).

### 3. Authorization Guardrails (Unhappy Path)
*Focus: Ensure that only the resource owner can update or delete.*
- **[MINIMAL] Non-Creator Update Rejected:** Assert that a user who is not the creator receives an `AuthorizationException` (or `403`).
- **[MINIMAL] Non-Creator Delete Rejected:** Assert that a user who is not the creator cannot delete the resource.
- **Edge Cases:** Test with `null` or unauthenticated users where applicable.

### 4. Side-Effects & Business Logic
*Focus: Test any automatic behavior or derived values produced by the service.*
- **[MINIMAL] Auto-Generated Values:** If the service generates values (e.g., slugs, timestamps), assert the output matches expectations in both creation and update scenarios.
- **Uniqueness / Collision Handling:** When the same title/text produces a collision, verify the service resolves it deterministically (numeric suffixes, random fallback, etc.) without throwing or silently overwriting.
- **State Transitions:** If the method has side effects (e.g., cache clearing, logging, notifications), verify they occur.

#### 5. Query & Pagination
*Focus: Verify data retrieval methods respect parameters and prevent N+1.*
- **[MINIMAL] Pagination:** Assert that paginated queries return the correct page size, total count, and paginator instance type.
- **[MINIMAL] Eager Loading:** Verify that relations are pre-loaded on the returned models to prevent N+1 queries (use `relationLoaded()`).
- **Filtering / Optional Includes:** If the query accepts parameters like `withComments`, test both enabled and disabled states.

> controllers / HTTP / API .. feature tests

### ...

---

## Blueprint: Controllers
*Focus: keep controllers thin and delegate real work to services.*
- Controllers should assemble validated input, authorize the request, and hand execution off to service methods.
- Avoid business logic, query construction, or repeated eager-loading setup in controllers.

---

## Blueprint: Service Layer
- Services should encapsulate business logic, query construction, and any side effects.
- Service queries should reuse model scopes for shared query logic, for example `withPostAndUserName()` instead of repeating the same eager-load configuration.

