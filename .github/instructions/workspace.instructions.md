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
- **Auth‑aware controllers:** For routes under the `auth:sanctum` middleware, extract the authenticated user into a private `user()` helper to avoid repeating `Auth::user()` casts in every method:
  ```php
  private function user(): User
  {
      $user = Auth::user();
      assert($user instanceof User);

      return $user;
  }
  ```
  Then use `$this->user()` in `store()`, `update()`, and `destroy()` instead of the manual `/** @var User $user */ $user = Auth::user();` block.
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

### Index Method Traits (`HasPerPage` & `HasOrderBy`)

Use `app/Http/Controllers/Api/Traits/HasPerPage.php` and `app/Http/Controllers/Api/Traits/HasOrderBy.php` in controllers that have an `index()` returning a paginated, sortable list.

**`HasPerPage`** — resolves `?per_page=N` from the query string, clamped between `1` and a configurable maximum:
```php
$this->getPerPage(default: 15, max: 100); // returns int
```

**`HasOrderBy`** — resolves `?orderby=column[_desc]` from the query string, validated against a whitelist:
```php
[$column, $direction] = $this->getOrderBy('created_at', 'desc');
// returns ['created_at', 'asc'] or ['created_at', 'desc']
```
- Supports a `_desc` suffix: `?orderby=updated_at_desc` → `['updated_at', 'desc']`.
- Invalid column names fall back to the defaults.
- **Must override `allowedOrderByFields(): array`** in the consuming controller (base returns `[]`, so unrecognized columns always fall back).

**When to use:**
- Any controller with a public `index()` endpoint that returns a paginated collection.
- Each controller can define its own allowed fields and default ordering through the overrides.
- The service's `query()` method must accept `$orderByColumn` and `$orderByDirection` parameters to apply the ordering.

**Usage pattern in `index()`:**
```php
use App\Http\Controllers\Api\Traits\HasOrderBy;
use App\Http\Controllers\Api\Traits\HasPerPage;

readonly class PostController
{
    use HasOrderBy;
    use HasPerPage;

    protected function allowedOrderByFields(): array
    {
        return ['published_on', 'updated_at'];
    }

    public function index(): PaginatedDataCollection
    {
        [$orderByColumn, $orderByDirection] = $this->getOrderBy('published_on', 'desc');

        return SomeData::collect(
            $this->service->query(
                perPage: $this->getPerPage(default: 6, max: 24),
                orderByColumn: $orderByColumn,
                orderByDirection: $orderByDirection,
            ),
            PaginatedDataCollection::class,
        );
    }
}
```

**Reference:** `app/Http/Controllers/Api/Blog/PostController.php` — full example with both traits in `index()`.

### Service Layer
- Encapsulate business logic, queries, and side effects.
- Reuse model scopes for shared query logic (e.g., `withPostAndUserName()`).
- Prefer Laravel collection helpers over raw PHP `array_*` functions when transforming DTO arrays or building payloads in services.
- Default to readonly properties and constructor injection. Use `readonly class` (PHP 8.4+).

### Content-Field Scope (`withoutContentFields`)

When a model has content-heavy columns (`body`, image blobs, etc.) that are irrelevant in certain contexts (e.g., a `Post` model listed inside a `Comment` response), use a dedicated scope to exclude them:

```php
/**
 * @param  Builder<Post>  $builder
 * @return Builder<Post>
 */
public function scopeWithoutContentFields(Builder $builder): Builder
{
    return $builder->select(['id', 'user_id', 'title', 'slug', 'published_on', 'created_at', 'updated_at']);
}
```

- **Where to define:** On the parent model (e.g., `Post`) as a local scope.
- **When to use:** In eager-load constraints on the **child side** — any relation that returns the parent model in a list/detail endpoint that doesn't need the full parent payload.
- **How to use in services:**

  ```php
  // In a service query method (builder scope)
  Comment::query()
      ->with(['post' => fn ($query) => $query->withoutContentFields()])
      ->with(['post.user', 'user']);

  // In a service find method (load scope)
  $comment->load(['post' => fn ($query) => $query->withoutContentFields()])
      ->load(['post.user', 'user']);
  ```

- **Benefit:** New content-heavy fields only need adding to the scope's `select()` in one place — all consumers automatically exclude them.
- **Reference:** `app/Models/Blog/Post::scopeWithoutContentFields()`, `app/Services/Blog/CommentService`. Also pair with a lightweight DTO (e.g., `PostCommentData`) that omits the same fields from serialization.

### DTO Style (Spatie Laravel Data)
- Accept typed DTOs in services — no `array<string, mixed>` signatures.
- Keep DTOs tiny: constructor-promoted public properties only, no business logic.
- Use `#[WithCast(DateTimeInterfaceCast::class)]` for Carbon dates with global mapping in `config/data.php`.
- Replace Carbon types in generated TypeScript via `TypeScriptTransformerServiceProvider`.
- Map optional DTO properties to Eloquent payloads with Laravel collection filtering instead of raw `array_*` helpers:
  ```php
  $data = collect([
      'title' => $dto->title,
      'body' => $dto->body,
      'published_on' => $dto->published_on,
  ])->filter(static fn (?string $value): bool => $value !== null)->all();
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
  - When the controller uses `HasPerPage` / `HasOrderBy`, also test:
    - Custom `per_page` returns correct count and `meta.per_page`.
    - `orderby` param returns items in expected order.
    - Invalid `orderby` falls back to default (no error).
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

---

## Vue 3 SPA Frontend

### Stack
- **Framework:** Vue 3 (`<script setup lang="ts">` syntax) with TypeScript.
- **Bundler:** Vite 8 via `laravel-vite-plugin` + `@vitejs/plugin-vue`.
- **Routing:** `vue-router` with `createWebHistory()` — no Inertia, pure client-side SPA.
- **HTTP:** Axios (`resources/js/api.ts`) with `/api` base URL.
- **Styling:** Tailwind CSS 4 (same theme as backend views).
- **State:** No Pinia yet — components use `ref`/`reactive` locally.

### Directory Structure

```
resources/js/
├── api.ts                  # Axios client (baseURL: /api)
├── app.ts                  # Entry point — creates Vue app + router, mounts #app
├── App.vue                 # Root component (AppLayout > router-view)
├── env.d.ts                # .vue module declarations
├── types.ts                # TypeScript backend DTOs (generated from Spatie Data)
├── layouts/
│   └── AppLayout.vue       # Base shell — AppHeader, <main> slot, AppFooter
├── components/
└── pages/
```

### Route Configuration

**Vue Router (`resources/js/app.ts`):**

**Catch-all in `routes/web.php`:** A `Route::get('/{any}', ...)->where('any', '.*')` must be the **last** web route so that all non-root paths serve the `welcome` Blade view, allowing Vue Router to resolve them client-side:

```php
Route::get('/{any}', fn (): Factory|View => view('welcome'))
    ->where('any', '.*');
```

### API Pattern

All API calls go through `resources/js/api.ts` (a preconfigured axios instance). The Laravel API lives under `/api/*` and returns JSON. Frontend routes that match the catch-all are handled by Vue Router, which then calls the API to hydrate the page.

**Example — fetching posts on mount:**
```typescript
import { onMounted, ref } from 'vue';
import type { PostData } from '@/types';
import api from '@/api';

const posts = ref<PostData[]>([]);

onMounted(async () => {
    const { data } = await api.get<{ data: PostData[] }>('/blog/posts');
    posts.value = data.data;
});
```

### TypeScript Types

- Backend Spatie DTOs (annotated with `#[TypeScript]`) are transformed to ambient types via `php artisan typescript:transform`.
- `resources/js/types.ts` TypeScript module exports all DTOs for frontend usage. Example:

```typescript
import type { PostData, UserData } from '@/types';
```

### Card and Table list pattern

- A list caller component should load its data, choose the card or row component, and pass `items`, the component reference, the item prop name, and optional display limits into a generic lister.
- `resources/js/components/common/CardLister.vue` is the generic list shell for grid/card UI. It renders a wrapper around each item, enforces `max-items`, and delegates per-item rendering using `<component :is="cardComponent" />`.
- `resources/js/components/common/TableLister.vue` is the generic table shell. It renders a responsive table wrapper, supports an optional `header` slot, enforces `max-rows`, and delegates per-row rendering using `<component :is="rowComponent" />`.
- Item renderer components should stay narrow: they receive only the item prop specified by the caller and render the card or row markup. Table row components may also accept a `columns` prop when the caller needs dynamic cell selection.
- Prefer `withDefaults(defineProps<...>(), {...})` in every Vue component so the template stays clean and the component defaults are visible at the top of the script. Only set defaults for optional props — required props should be left without defaults to enforce compile-time checks.
- Example props block:

```ts
const props = withDefaults(defineProps<{
    title: string;
    endpoint?: string;
    description?: string;
    cardComponent?: Component;
    cardPropName?: string;
    maxItems?: number;
    emptyText?: string;
}>(), {
    endpoint: '/blog/posts',
    description: 'A live overview of the most recent blog posts.',
    cardComponent: PostCard,
    cardPropName: 'post',
    maxItems: 6,
    emptyText: 'No posts available.',
});
```

- Example call structure of (lister like card and table) components:

```vue
<template>
  <CardLister
    :items="posts"
    :card-component="PostCard"
    card-prop-name="post"
    :max-items="6"
  />
</template>
```

- This keeps the homepage lean: data fetching and section metadata stay in the caller, layout and iteration live in the lister, and content markup stays inside the card component.
- The same pattern can be reused for future variants like `TableLister` with a different rendering shell and inner row component.

The path alias `@/` resolves to `resources/js/` (configured in both `tsconfig.json` and `vite.config.js`).

**Available types (`resources/js/types.ts`):**

### Component Conventions

1. **Layout components** go in `layouts/` — wrap `<slot />` with shared chrome (header, nav, footer).
2. **Page components** go in `pages/` — lazy-loaded via `() => import(...)`, fetch their own data on mount.
3. **Reusable UI components** go in `components/` — receive data via `defineProps`, emit events or rely on router links for navigation.
4. **Page-state components** (loading, empty, error) are inline in the same page component rather than abstracted, unless reused across 3+ pages.
5. **Every component uses `<script setup lang="ts">`** with explicit prop and emit types.
6. **Keep components focused** — `PostCard` renders one post card, `CardLister` renders a grid of `PostCard`. No single component does both.
