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

## Model & Factory Testing Standards
When creating or modifying Eloquent models, you must write comprehensive Pest tests to verify the database and hydration layer before moving to services or controllers.

### Execution Style
- Write tests using **Pest PHP** functional syntax (`it()`, `expect()`).
- Group tests for the same model logically using `describe('ModelName', function () { ... })` blocks for structure and scannability.
- Place integration/database-interacting tests inside `tests/Feature/Models/` and pure isolated logic in `tests/Unit/`.
- Always include `uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);` at the top of files interacting with the schema.

---

### Strict Blueprint: Minimal Required Test Scope

Every model file must be accompanied by a Pest test file covering these three distinct `describe()` categories. You must implement at least the **absolute minimum** requirements listed below:

#### 1. Configuration & Data Integrity
*Focus: Model hydration and serialization.*
- **[MINIMAL] Factory Validation:** Assert that the model's factory generates valid data, instantiates the correct class, and successfully persists to the database.
- **[MINIMAL] Cast Verification:** Explicitly test custom/critical attribute casting (e.g., datetimes, booleans, arrays, enums). This is the most vital configuration to test as errors break the hydration layer.
- **Mass Assignment & Security:** Verify `fillable` guardrails and `hidden` attributes (ensuring sensitive fields do not leak during serialization like `toArray()` or `toJson()`).

#### 2. Relationship Integrity (Happy Path)
*Focus: Eloquent relationship execution and hydration.*
- **[MINIMAL] BelongsTo:** Assert that a child model correctly returns an instance of its parent model, matching the exact keys.
- **[MINIMAL] HasMany / Many-to-Many:** If a model can own multiple child records, test the relationship by creating multiple entities (e.g., count of 3) and assert the collection count and instance type match.
- **Pivots:** Verify intermediate table connections, including custom pivot table attributes or specialized pivot casting if applicable.

#### 3. Database Constraints & Rules (Unhappy Path)
*Focus: Ensuring the database enforces constraints independently of application logic.*
- **[MINIMAL] Strict Foreign Keys:** Ensure database-level constraints work as intended. Assert that a `QueryException` is thrown when trying to save a model without its mandatory foreign parent relation (e.g., a child record cannot exist as an orphan without its parent ID).
- **Cascade Operations:** Assert that `cascadeOnDelete()` parameters function correctly by deleting a parent model and verifying the automatic removal of child records from the database.