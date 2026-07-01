# Timeline

## create base model migration factory files for blog

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

## create blog post service + tests

create a `app/Services/Blog/PostService.php` , in relation to `app/Models/Post.php`

we need the next methods:

- create
- update
- delete
- query (with pagination, with comments, with user id+name, prevent n+1)

important only the creator can update/delete

## create blog post comment service + tests

create a `app/Services/Blog/CommentService.php` , in relation to `app/Models/Comment.php`

we need the next methods:

- create
- update
- delete
- query (with pagination, with posts, with user id+name, prevent n+1)

important only the creator can update/delete
