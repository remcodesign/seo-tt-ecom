# Timeline

`Use the **laravel boost** tool to ensure compliance with the latest Laravel 13 patterns for API authentication and modern PEST testing syntax.`

## DONE - create base model migration factory files for blog

docs/private/todo/done/1-blog-main-migration.md

-------------

## DONE - create blog post service + tests

docs/private/todo/done/2-blog-post-service-test.md

-------------

## DONE - create blog post-comment service + tests

docs/private/todo/done/3-blog-comment-service-test.md

-------------

## DONE - create blog post routes/request validation classes + controlers - no test

docs/private/todo/done/4-blog-route-validation-controller-no-test.md

-------------

## DONE - add 'Laravel Sanctum' auth + custom user registration (API only)

docs/private/todo/done/5-sanctum-user-reg-controller-guard-test.md

-------------

## DONE - model casts, `datetime` to `immutable_datetime` - this for no side-effects with carbon > CarbonImmutable and change default in `AppServiceProvider`

docs/private/todo/done/6-default-immutable-carbon-classes.md

-------------

## DONE - fixed ddev db port

-------------

## DONE - `app/Http/Controllers/Api/Blog/PostController.php` using `Auth::user()` instead of the request object

-------------

## DONE - adding code coverage check

-------------

## DONE - prepent `blog` before `post / comments` tables

-------------

## DONE - prepent `blog` before `post` and `comment` classes/routes

-------------

## DONE - goto phpstan level 9 and use `spatie/laravel-data` DTO's for the whole stack - no RAW array passing anymore

docs/private/todo/done/7-dto-plus-phpstan-level9.md

-------------

## DONE - adding `comments` controller + tests - with the same style as the `post` controller

-------------

## DONE - make `blog` `post` and `comments` methods `index` and `show` public in the routes and tests

-------------

## DONE - seed the full blog (post + comments + users)

-------------

## DONE - blog post slug should be used for the show method

-------------

## TODO - user add fields `is_admin(bool)` `role_label(varchar)` to store a short label + change tests + seeder to use these fields + role_label ENUM

- create role_label enum (guest, user, writer, admin)

## TODO - blog post `published_on = null` should not be visible with the normal index call

<!-- ## TODO - postman test + sanctum > send full post/comment objects -->

> vue
>
## TODO - start een simple vue frontend with components for common parts

## TODO - frontend login via api - (no roles/permissions)

## TODO - add create/update/delete comments (when logged-in)

## TODO - add policies, user roles/permissions, spatie/laravel-permission

docs/private/todo/policies-roles-permission.md

## TODO - add blog categories, tags

## TODO - simple blog - add login > then can comment(write/update) via user

## TODO - `value-objects` for project concepts (slug, money, invoice)

docs/private/todo/value-object.md

## TODO - domain invariants - exception handling

docs/private/todo/domain-invariant.md

> blade?
>
## TODO - simple blog - blade home / post-index / post-show + comments(read) + seeds + web.php routes
