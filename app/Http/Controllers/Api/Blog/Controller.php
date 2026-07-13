<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Blog;

use App\Enums\RoleLabel;
use App\Http\Controllers\Api\Controller as ApiController;
use App\Models\Blog\Comment;
use App\Models\Blog\Post;

abstract class Controller extends ApiController
{
    protected function authorizeCommentOwnerOrAdmin(Comment $comment): void
    {
        $user = $this->user();

        if ($user->isNot($comment->user) && $user->role_label !== RoleLabel::admin) {
            abort(403, 'You are not authorized to modify this comment.');
        }
    }

    protected function authorizePostOwnerOrAdmin(Post $post): void
    {
        $user = $this->user();

        if ($user->isNot($post->user) && $user->role_label !== RoleLabel::admin) {
            abort(403, 'You are not authorized to modify this post.');
        }
    }
}
