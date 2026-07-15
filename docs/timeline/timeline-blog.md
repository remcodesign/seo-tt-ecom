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

## DONE - user add fields `is_admin(bool)` `role_label(varchar)` to store a short label + change tests + seeder to use these fields + role_label ENUM

-------------

## DONE - blog post `published_on = null` should not be visible with the public index and show api calls

-------------

<!-- ## TODO - postman test + sanctum > send full post/comment objects -->

> vue
>
## TODO - start een simple vue frontend with components for common parts

- use `laravel boost` and `context 7` to get the most modern vue3 vite within laravel
- first only a base layout - setup to make vite / vue3 SPA (without inertia) inside the codebase `resources` - keep the styling very simple - just a bit contrast

- then fill the current home `resources/js/pages/HomePage.vue`, so it contains a demo (dummy data) card based  (create a component for this) lister  (create a component for this) of the last 6 posts (with dummy link to post show page and writer data on the card)
  - use typescript `resources/js/generated/generated.d.ts` (use/create path alias) to type check and possible data and to fill the demo post data

- add a post show page (with user/writer data) - 'it should be on `/blog/posts/xxx`' so in the blog url context - use axios

```php
    Route::apiResource('posts', PostController::class)
        ->scoped(['post' => 'slug'])
        ->only(['index', 'show']);
```

- make the home page dynamic with post index data

- automatic converter from `resources/js/generated/generated.ts` to  `resources/js/types.ts` - keeping both - nice readble file in the first and easy usage in the next

- make a component of the `homepage` blog-post-card-lister, so we can place more variants more of them and no polution

- create folders for the `blog` context pages and components

- create table lister based on `resources/js/components/common/CardLister.vue` - then create a `CommentRow` based on `resources/js/components/blog/PostCard.vue` - with the typescript type of `CommentData` from `resources/js/types.ts` - with dynamic columns rendering

- on the `post show` page `resources/js/pages/PostShowPage.vue`, now add comments (create a component for this) lister in table form (reuse or create a component for this) (with user data) - use axios

- remove the email from the user-data object

- types.ts via flatwriter - keep the namespaced variant, but only to read as a doc, the types.ts is the one who is connected
  - remove the action and job and the command for the conversion of the namespaced one to the module one

- rename response DTO's, with `response` in the name appended

- remove optional/null from `public ?UserData $user = null, // relation` in `app/Data/Blog/Responses/PostData.php` - gives test error currently

- convert to PEST format `tests/Feature/Api/Traits/HasOptionalIncludesTest.php`

- postController, remove the relation adder from store and update, clean postDataModified of relations and this the same for other `Modified's`

- first do not add any complex additions to postController index and show

- add a `post index` page with card lister (reuse card component and lister) - 'it should be on `/blog/posts`' so in the blog url context - use axios

- let the `post index` order and per page work

- add a `comment index` page with a table style lister (reuse components for comment item  and lister) and references to the post and user - 'it should be on `/blog/comments`' so in the blog url context

- create common button.vue component - and refactor links/buttons in the current vue3 codebase

- add `goto links` to the post index, below the featured post cards on the `homepage`

- add `FeaturedComments` on the  `homepage` as table lister, below the `#sym:FeaturePosts`  - in the code-style of `resources/js/components/blog/homepage/FeaturePosts.vue` but then with comments presented with the `TableLister` and also add the `View all blog comments` below the feature list to the the comment index, max 3 comments
  - extra info how to use the tablelister with the comments `resources/js/pages/blog/CommentIndexPage.vue`

- add eslint fixer, to cleanup js

## DONE - frontend login via api - (no roles/permissions) - using current user `role_labels` and `admin`

- cleanup the `resources/js/components/AppNav.vue` file, make components/composables of the login parts

## DONE - add create/update/delete comments (when logged-in) - check `app/Http/Controllers/Api/Blog/CommentController.php` and the types for modifing for the correct data to send and receive

- so update `resources/js/pages/blog/PostShowPage.vue`
  - `create` `update` `delete`

- resources/js/components/blog/CommentRow.vue move code to composable (or not) - !we did not move the state part to a separate composable. for a next more simple admin page, create a more simple state `CommentRow.vue` (in the admin context), but reuse the current `resources/js/composable/blog/usePostComments.ts`

- use `<FontAwesomeIcon :icon="byPrefixAndName.fas['trash']" />` as delete button on `resources/js/components/blog/CommentRowActions.vue

- remove `is_admin` from the user object we have `role_label` for that
- move user owner check from service to controller for both `comment` and `post` for `store` and `update` methods, more early gate

## DONE - add browser tests - start with `pages/blog/PostShowPage.vue` the comments CRUD options for (non users, users, comment owners)

## REMOVED - BLADE - (we now use livewire instead of pure BLADE) add admin for user from `menu > admin item` - when loggedin as admin - Maybe use only Pure Blade for this part, to test/implement this style

## DONE - LIVEWIRE - add admin for user from `menu > admin item` - when loggedin as admin - Maybe use only Pure livewire V3 for this part, to test/implement this style

>HERE

## TODO - convert livewire v3 to livewire v4 - and all the extra's?

## TODO - use flux for the frontend components?

>THEN

## TODO - add admin for blog (crud posts)

## TODO - add admin for blog (crud comments)

## TODO - make the website visual responsive correct, use: desktop normal, tablet portrait, mobile portrait

## TODO - add policies, user roles/permissions, spatie/laravel-permission

docs/private/todo/policies-roles-permission.md

## TODO - add blog categories, tags

## TODO - `value-objects` for project concepts (slug, money, invoice)

docs/private/todo/value-object.md

## TODO - domain invariants - exception handling

docs/private/todo/domain-invariant.md

> blade?
>
## TODO - simple blog - blade home / post-index / post-show + comments(read) + seeds + web.php routes
