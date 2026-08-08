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
- **Livewire bindings:** In Livewire v3, `wire:model` is deferred by default and is not always the correct choice for form UI that must react immediately. Use `wire:model.live` for real-time filters and other interactive inputs that should trigger updates immediately, especially on lists and table filters.

## Livewire

- Prefer small, focused Livewire components rather than monolithic pages. Use nested partial components for filter/search controls, table rows, and isolated UI sections.
- Pass props into nested Livewire components via `@livewire(SomeComponent::class, ['prop' => $value])` and keep the parent responsible for the shared state.
- Extract form state and validation into a dedicated `Livewire\Form` sub-class (e.g. `app/Livewire/Admin/Users/UserForm.php`). Declare `public UserForm $form;` on the component and keep form fields inside the form class — this keeps the full-page component clean.
- Use `#[Computed]` for derived properties that don't change during a request, like pre-computed filter options. This caches the result for the duration of the request. Example: `#[Computed] public function roleLabels(): array`.
- Use `#[On('eventName')]` attribute on listener methods instead of the legacy `$listeners` array. This is the modern Livewire v3 syntax and keeps event wiring explicit at the method level.
- In Livewire v3, use `dispatch('eventName', $payload)` in child components for events that should bubble up. In the parent component, use `#[On('eventName')]` to listen.
- Use `updatedPropertyName()` lifecycle hooks only indirectly via `wire:model.live` or property changes, not by calling them directly in tests or controllers.
- Keep state explicit: `public string $search = ''`, `public string $roleLabelFilter = 'all'`, and initialize prop lists in `mount()` when needed.
- Use `wire:key` on repeated elements like option rows or table rows to preserve DOM stability and avoid render glitches.
- Test Livewire components with `Livewire::test()` for both parent behavior and child event dispatching: use `assertDispatched()` for `dispatch()` events and use `assertSet()` / `assertSee()` for parent state changes.
- When asserting raw HTML markup or Livewire attributes in component output, prefer `assertSeeHtml()` rather than `assertSee()`. `assertSee()` escapes HTML, so markup like `wire:click="delete(1)"` should be asserted with `assertSeeHtml()`.

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
  // In a service find method (load scope)
  $comment->load(['post' => fn ($query) => $query->withoutContentFields()])
      ->load(['post.user', 'user']);
  ```

- **Benefit:** New content-heavy fields only need adding to the scope's `select()` in one place — all consumers automatically exclude them.
- **Reference:** `app/Models/Blog/Post::scopeWithoutContentFields()`, `app/Services/Blog/CommentService`. Also pair with a lightweight DTO (e.g., `PostCommentData`) that omits the same fields from serialization.

### DTO Style (Spatie Laravel Data)
- Accept typed DTOs in services — no `array<string, mixed>` signatures.
- Keep DTOs tiny: constructor-promoted public properties only, no business logic.
- Use strict types on DTO properties. Prefer `CarbonImmutable|null` for date fields and avoid broad unions like `CarbonImmutable|string|null` unless the DTO genuinely stores raw string input.
- Use `#[WithCast(DateTimeInterfaceCast::class)]` only on date DTO properties, with explicit formats when required by multiple input sources.
- Replace Carbon types in generated TypeScript via `TypeScriptTransformerServiceProvider`.
- Keep DTOs responsible for representing the final payload shape and avoid service-side prefiltering when the DTO is already configured correctly.
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

### Date handling for nullable form fields
- For date inputs like `published_on`, normalize blank values to `null` before creating DTOs: use `filled($this->form->published_on) ? $this->form->published_on : null`.
- Livewire form objects may store date fields as `string|null`; do not rely on the browser date input to preserve a Carbon instance.
- Request DTOs should support both browser `Y-m-d` values and API/seed `Y-m-d H:i:s` values when the same field is used in multiple contexts.
- Use `#[WithCast(DateTimeInterfaceCast::class, ['Y-m-d', 'Y-m-d H:i:s'])]` on date DTO properties when the value may arrive as either format.
- In service updates, preserve explicit `published_on: null` in the payload so clearing a date really writes `NULL` instead of omitting the field.
- Prefer `CarbonImmutable|string|null` on DTO date properties only when necessary to accept both raw strings and cast values. So first only CarbonImmutable, then add `string` if the same DTO is used for both API and form input. Otherwise, keep the type strict to avoid accidental string usage in services. > The DTO should represent the final cast value, not the raw input.

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

## Testing

Follow the reusable Pest and Laravel testing guidance in `.github/skills/laravel-pest-testing/SKILL.md`.

For this project, run the affected tests first with `php artisan test --compact`. After PHP changes, run `ddev composer format-basic` and the configured static analysis checks when relevant.

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

### Frontend Browser Testing with Pest 4

- Prefer browser tests for end-to-end UI coverage in `tests/Browser/`.
- Use `data-test` attributes on Vue elements to decouple tests from styling and translations.
- Select test elements in Pest 4 using standard CSS attribute selectors, for example:

```php
$page->fill('[data-test="login-email-input"]', 'user@example.com')
     ->click('[data-test="login-submit-button"]');
```

- Add `data-test` to interactive controls that are important for flows, such as:
  - login buttons and form fields
  - comment input and submit controls
  - comment edit/save/delete buttons

- Avoid relying on visible text for selectors when the element has a stable `data-test` attribute.
- Keeping these test tags short and consistent makes tests resilient to class changes, translations, and markup refactors.

- `data-test` is preferred over non-standard attributes like `test="..."`, since it remains valid HTML5 and is clearly intended for test automation.
- If you need a broader selector, use CSS wildcard selectors such as `[data-test*="submit"]` to match any attribute value containing `submit`.

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

### Universal Components

Always use these shared components as the default when styling needs match. They keep the design system consistent and reduce duplication.

**`resources/js/components/common/Card.vue`** — the default card wrapper for any content that goes inside a rounded, bordered container:
```vue
<Card class="flex flex-col">
    <!-- any content -->
</Card>
```
- Accepts any HTML attribute or class via `$attrs` (e.g. `class="flex flex-col"`).
- Use whenever you need a card-style container — do not re-implement the border/background/shadow styles manually.

**`resources/js/components/common/Button.vue`** — universal button and link component with 4 variants:

| Variant | Appearance | Use case |
|---------|-----------|----------|
| `bordered_normal` | Bordered button with active/disabled states | Pagination, action buttons |
| `nav` | Pill-style with active highlight | Navigation menu items |
| `text` | Plain text, no underline | Back links, card titles |
| `text-underline` | Dotted underline | Inline links (e.g. post title in a comment row) |

**Props:**
- `variant` — one of the variants above (default `bordered_normal`)
- `size` — 'xs' | 'sm' | 'md' | 'lg' (default `md`)
- `disabled` — disables button (ignored when `to` is set)
- `active` — applies the active/highlighted state
- `to` — a vue-router route location; when set, renders a `<router-link>` instead of a `<button>`

**Usage examples:**
```vue
<!-- Pagination button -->
<Button variant="bordered_normal" size="sm" :active="link.active" :disabled="link.page === null">
    {{ link.label }}
</Button>

<!-- Navigation link -->
<Button variant="nav" size="md" :active="isActive" @click="navigate">
    Blog
</Button>

<!-- Text link to a post -->
<Button variant="text-underline" :to="{ name: 'posts.show', params: { slug: post.slug } }">
    {{ post.title }}
</Button>

<!-- Back link -->
<Button variant="text" class="gap-2" @click="goBack">
    <span>←</span><span>Back</span>
</Button>
```

- Always prefer `Button` over raw `<button>` or `<router-link>` when visual styling is needed.
- Use the `to` prop for navigation instead of `@click` + `router.push()` when possible.
- Keep location-specific sizing/positioning as `class` overrides (e.g. `class="min-w-[3rem]"` on pagination buttons).

### Component Conventions

1. **Layout components** go in `layouts/` — wrap `<slot />` with shared chrome (header, nav, footer).
2. **Page components** go in `pages/` — lazy-loaded via `() => import(...)`, fetch their own data on mount.
3. **Reusable UI components** go in `components/` — receive data via `defineProps`, emit events or rely on router links for navigation.
4. **Page-state components** (loading, empty, error) are inline in the same page component rather than abstracted, unless reused across 3+ pages.
5. **Every component uses `<script setup lang="ts">`** with explicit prop and emit types.
6. **Keep components focused** — `PostCard` renders one post card, `CardLister` renders a grid of `PostCard`. No single component does both.
