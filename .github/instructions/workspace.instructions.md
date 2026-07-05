---
description: Project-specific workspace instructions for Laravel application code and review.
applyTo: '**/*'
---

## Project Context

- **Stack:** Laravel 13 on PHP 8.4. Follow Laravel conventions and existing app structure.
- **Testing:** Pest PHP 4. Verify changes with `php artisan test --compact`.
- **Formatting:** Run `ddev composer format-basic` after PHP changes (Pint + Rector).
- **Static analysis:** Maintain configured PHPStan level, including generic relation docblocks (`@return HasMany<Post, $this>`).
- **Dependencies:** Do not add new ones unless explicitly requested.
- **Scaffolding:** Prefer `php artisan make:*` when it fits project conventions.
- **Alignment:** Keep `app/Models`, `database/migrations`, `database/factories` consistent.

---

## Architecture Patterns

### Controllers
- Keep thin: assemble validated input, authorize, delegate to services.
- No business logic, query construction, or repeated eager-loading setup.
- Prefer invokable controllers for single-action endpoints.
- Use `app/Http/Controllers/Api/Traits/HasOptionalIncludes.php` for optional response expansion.
  - Add `use HasOptionalIncludes;` to the controller and implement `protected function allowedIncludes(): array`.
  - Most often use this trait in `store()` and `update()` methods for minimal default JSON output.
  - In `store()` / `update()`:
    - create/update the model via the service
    - call `[$model, $includes] = $this->resolveOptionalIncludes($model);`
    - create the DTO: `$dto = DataClass::from($model);`
    - call `$this->applyIncludes($dto, $includes);`
    - return `$dto;`
  - Example (`CommentController`):
    ```php
    [$comment, $includes] = $this->resolveOptionalIncludes($comment);

    $commentData = CommentData::from($comment);
    $this->applyIncludes($commentData, $includes);

    return $commentData;
    ```
  - Allowed relations are explicit and only loaded when requested, e.g. `?include=user` or `?include=post.user,user`.

### Service Layer
- Encapsulate business logic, queries, and side effects.
- Reuse model scopes for shared query logic (e.g., `withPostAndUserName()`).
- Default to readonly properties and constructor injection. Use `readonly class` (PHP 8.4+).

### DTO Style (Spatie Laravel Data)
- Accept typed DTOs in services — no `array<string, mixed>` signatures.
- Keep DTOs tiny: constructor-promoted public properties only, no business logic.
- Use `#[WithCast(DateTimeInterfaceCast::class)]` for Carbon dates with global mapping in `config/data.php`.
- Replace Carbon types in generated TypeScript via `TypeScriptTransformerServiceProvider`.
- Map optional DTO properties to Eloquent payloads with compact `array_filter`:
  ```php
  $data = array_filter([
      'title' => $dto->title,
      'body' => $dto->body,
      'published_on' => $dto->published_on,
  ], static fn (?string $value): bool => $value !== null);
  ```
- Add `#[TypeScript]` above DTO classes for frontend type generation (`php artisan typescript:transform`).
- Use nullable DTO properties for optional relations and response expansions. Keep default response payloads minimal by defining optional relation fields with default `null`, for example:
  ```php
  public function __construct(
      public int $id,
      public int $user_id,
      public ?UserData $user = null,
  ) {}
  ```
  This lets controllers return the full model by default and only include extra relation data when requested via `?include=`.
- Reference pattern: `app/Data/Auth/RegisterData.php` → `RegisterUserController` → `UserService` → `UserData`.

---

## Sanctum API Auth

Custom endpoints (no Fortify). Routes in `routes/api.php`:

| Method | URI | Purpose | Auth |
|--------|-----|---------|------|
| `POST` | `/api/users` | Register | None |
| `POST` | `/api/sanctum/token` | Issue token | None |
| `DELETE` | `/api/sanctum/tokens/current` | Revoke token | `auth:sanctum` |

- **Registration** (`RegisterUserController` → `UserService`): expects `name`, `email`, `password`, `password_confirmation`. Returns `201` with `{message, user}`.
- **Token creation** (`CreateTokenController`): validates email/password, returns `{token}`. Invalid credentials → 422 (no user enumeration).
- **Token revocation** (`RevokeTokenController`): deletes current bearer token. Returns `{message: "Token revoked."}`.
- **Config:** `bootstrap/app.php` uses `$middleware->statefulApi()`. Exception handler renders JSON for `api/*` routes.

---

## Testing Standards

Also consult `.github/skills/pest-testing/SKILL.md` for additional guidance.

### General Conventions
- Use Pest syntax (`it()`, `expect()`) with `describe()` blocks for structure.
- Integration/database tests go in `tests/Feature/Models/`; isolated logic in `tests/Unit/`.
- Include `uses(RefreshDatabase::class)` in files interacting with the schema.

### Model Tests — Three Required `describe()` Blocks

**1. Configuration & Data Integrity**
- **[MINIMAL]** Factory: valid data, correct class, persists to DB.
- **[MINIMAL]** Casts: test custom/critical casting (datetimes, booleans, arrays, enums).
- Mass assignment: verify `fillable` guardrails and `hidden` fields don't leak in `toArray()`/`toJson()`.

**2. Relationship Integrity (Happy Path)**
- **[MINIMAL]** `BelongsTo`: child returns parent with matching keys.
- **[MINIMAL]** `HasMany`/many-to-many: create multiple children, assert count + instance type.
- Pivots: verify intermediate table connections and custom pivot attributes.

**3. Database Constraints (Unhappy Path)**
- **[MINIMAL]** Foreign keys: assert `QueryException` when saving without mandatory parent.
- Cascade: delete parent, verify child records auto-removed.

### Service Tests — Four Required `describe()` Blocks

Scope: test business behavior, not model schema. Avoid duplicating low-level assertions from model tests.

**1. Happy Path — Creation**
- **[MINIMAL]** Successful creation: correct instance, persisted, correct attributes.
- **[MINIMAL]** Ownership: resource linked to authenticated user/parent.

**2. Happy Path — Update & Delete**
- **[MINIMAL]** Update: modifies intended attributes without side effects.
- **[MINIMAL]** Delete: removes record from DB.
- Partial updates: subset of attributes leaves rest untouched.

**3. Authorization Guardrails (Unhappy Path)**
- **[MINIMAL]** Non-creator update rejected (`AuthorizationException` / 403).
- **[MINIMAL]** Non-creator delete rejected.
- Edge cases: `null` or unauthenticated users.

**4. Side Effects & Business Logic**
- **[MINIMAL]** Auto-generated values (slugs, timestamps) match expectations.
- Collision handling: deterministic resolution without silent overwrites.
- State transitions: cache clearing, logging, notifications.

**5. Query & Pagination**
- **[MINIMAL]** Pagination: correct page size, total count, paginator type.
- **[MINIMAL]** Eager loading: use `relationLoaded()` to verify N+1 prevention.
- Filtering / optional includes: test enabled and disabled states.

### API Controller Tests

Verify the full HTTP lifecycle without duplicating service-layer assertions.

**Happy Path Per Action**
- `index`: `assertSuccessful()`, JSON has `data`, `meta`, `links`.
- `show`: `assertSuccessful()`, contains resource ID and attribute structure.
- `store`: `assertCreated()`, response has resource attributes, DB has record.
- `update`: `assertSuccessful()`, fresh model confirms persistence.
- `destroy`: `assertNoContent()`, `Model::find($id)` is null.

**Unauthenticated Guardrails**
- **[MINIMAL]** One `assertUnauthorized()` test per `auth:sanctum` route group (e.g., on `index`) is sufficient.
- Use `$this->getJson()`, `$this->postJson()`, `$this->putJson()`, `$this->deleteJson()` — not `post()`/`get()`.

**Authentication Strategy**
- `Sanctum::actingAs(User::factory()->create())` for authenticated requests.
- For ownership checks: create two users (owner + other), assert `assertForbidden()` on non-owner update/destroy.

**Validation Errors**
- `postJson()` with missing/invalid data → `assertUnprocessable()` + `assertJsonValidationErrors(['field'])`.
- Test required fields, format validation, unique constraints.

**Reference:** `tests/Feature/Api/Blog/PostControllerTest.php` — each CRUD action in its own `describe()` block.
