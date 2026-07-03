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

## TODO - `app/Http/Controllers/Api/Blog/PostController.php` using `Auth::user()` instead of the request object

- change some of the methods to full public, this for tests and routes
  - index and show need to be full public
    - change this in the api.php routes file
  - all modify routes (not pure get routes) stay on the current sanctum groups
    - change tests, move the index http401 to the create test `tests/Feature/Api/Blog/PostControllerTest.php`

## TODO - goto phpstan level 9 and use `spatie/laravel-data`

docs/private/todo/dto-plus-phpstan-level9.md

## TODO - adding code coverage check

## TODO - prepent `blog` before `post / comments` tables

## TODO - prepent `blog` before `post` classes/routes

## TODO - adding comments controller + tests

## TODO - prepent `blog` before `comments` classes/routes

## TODO - simple blog - blade home / post-index / post-show + comments(read)

## TODO - simple blog - add login > then can comment(write/update) via user

## TODO - `value-objects` for project concepts (slug, money, invoice)

docs/private/todo/value-object.md

## TODO - domain invariants - exception handling

docs/private/todo/domain-invariant.md
