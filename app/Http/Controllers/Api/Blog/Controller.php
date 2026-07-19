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

        // A writer user cannot update a post that is not their own.
        // .. so the user is the owner, then allow, otherwise abort
        // .. so the user is not(!) the owner, but the user is(!) an admin, then allow, otherwise abort
        if (
            // Check if the user is not the owner of the post
            $user->isNot($post->user)
            &&
            // Check if the user is not an admin
            $user->role_label !== RoleLabel::admin
        ) {
            abort(403, 'You are not authorized to modify this post, only for the owner or an admin.');
        }
    }
}
