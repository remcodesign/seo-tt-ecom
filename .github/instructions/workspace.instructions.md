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

## Tests in general

### Execution Style
- Write tests using **Pest PHP** functional syntax (`it()`, `expect()`).
- Place integration/database tests inside `tests/Feature/Models/` and pure isolated logic in `tests/Unit/`.
- Always utilize the `RefreshDatabase` trait for tests interacting with the schema.

## Model & Factory Testing Standards
When creating or modifying Eloquent models, you must write comprehensive Pest tests to verify the database and hydration layer before moving to services or controllers. 

Ensure tests cover the following areas:

### 1. Configuration & Data Integrity
- **Factory Verification:** Ensure the model's factory generates valid data and can successfully persist to the database.
- **Attribute Behavior:** Verify property behavior including `casts` (e.g., datetimes, booleans, arrays), `fillable` restrictions, and `hidden` fields.

### 2. Relationship Integrity (Happy Path)
- **BelongsTo / HasMany:** Test basic relationships to ensure proper instantiation and hydration of related models.
- **Many-to-Many / Pivots:** Verify intermediate table connections, including custom pivot attributes or casting if applicable.

### 3. Database Constraints & Rules (Unhappy Path)
- **Cascade & Foreign Keys:** Ensure database-level constraints work as intended (e.g., an exception is thrown when trying to persist a model without its mandatory parent relation).
- **Logical Bounds:** Test invalid states where applicable (e.g., verifying that orphaned records or incorrect polymorphic mappings are rejected by database queries or constraints).