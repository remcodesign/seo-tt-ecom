# Timeline

## DONE - create base model migration factory files for blog

use the tool laravel boost and create the:
 migrations (database/migrations) and models (app/Models) including relations and indexes, cast, factories (database/factories)

all this for we need to create the next models:

we have the model `app/Models/User.php`

> post

- 1 user can have many post
- a post belongs to a user

```txt
user_id, title (varchar), slug (varchar), published_on (datetime), body (text)
```

> comment

- 1 user can have many comments per post
- a comment belongs to a user

```txt
post_id, user_id, comment (text)
```

## DONE - create blog post service + tests

create a `app/Services/Blog/PostService.php` , in relation to `app/Models/Post.php`

we need the next methods:

- create
- update
- delete
- query (with pagination, with comments, with user id+name, prevent n+1)

important only the creator can update/delete

-------------

## DONE - create blog post comment service + tests

create a `app/Services/Blog/CommentService.php` , in relation to `app/Models/Comment.php`

we need the next methods:

- create
- update
- delete
- query (with pagination, with posts, with user id+name, prevent n+1)

important only the creator can update/delete

-------------

## DONE - create blog post routes/request validation classes + controlers - no test

- first `install` the api.php in `routes` route and activate it in `bootstrap/app.php`

- we have multiple methods in the post service `app/Services/Blog/PostService.php` resulting in resourcefull api methods of:

- index
- show
- store
- update
- destroy

> now create the matching routes in `routes/api.php` like `Route::apiResource(..`

> now create the matching request validation classes in the folder `app/Http/Requests/Api/Blog`

> create the matching controller and bind the: `routes > ?validators > service > response`

> run `ddev composer format` and fix errors in the most lean way

use laravel boost tool, to get the latest way of setting up the api and using resourcefull api+methods

-------------

## TODO - add 'Laravel Sanctum' auth via api.php middleware + add 'Laravel Fortify' for user creation - API only

> READ : `.github/instructions/workspace.instructions.md`

Ensure we have a minimal user setup for the API/HTTP tests.

### 1. API + Sanctum Setup

Install via:

```bash
ddev artisan install:api
```

Ensure `App\Models\User` uses the `Laravel\Sanctum\HasApiTokens` trait.

### 2. Fortify Setup (API only)

Install via:

```bash
ddev composer require laravel/fortify
ddev artisan fortify:install
```

Configure Fortify to disable views:

```php
// config/fortify.php
'views' => false,
```

### 3. Sanctum Stateful Middleware

Configure `bootstrap/app.php` to enable stateful API middleware:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi();
})
```

Update the `.env` file with the correct domains based on `ddev describe`:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000,current-project.ddev.site
```

### 4. Create Pest Feature Tests for PostController

Protect `Route::apiResource('posts', PostController::class);` with the `auth:sanctum` middleware in `routes/api.php`.

Create a high-level happy path test file using PEST style, grouping the following operations:

- index
- show
- store
- update
- destroy

**Testing Rule:** Use `Sanctum::actingAs(User::factory()->create())` within the tests to simulate the authenticated user instead of executing a multi-step HTTP login sequence through Fortify.

Use the **laravel boost** tool to ensure compliance with the latest Laravel 13 patterns for API authentication and modern PEST testing syntax.

-------------
