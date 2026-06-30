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