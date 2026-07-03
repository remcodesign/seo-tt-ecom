<?php

declare(strict_types=1);

namespace App\Models\Blog;

use App\Models\User;
use Database\Factories\Blog\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Post $post
 * @property-read User $user
 */
#[Fillable(['post_id', 'user_id', 'comment'])]
#[Table(name: 'blog_comments')]
class Comment extends BlogRootModel
{
    /**
     * @use HasFactory<CommentFactory>
     */
    use HasFactory;

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope the query to include the comment user id and name only.
     *
     * @param  Builder<Comment>  $builder
     * @return Builder<Comment>
     */
    public function scopeWithUserName(Builder $builder): Builder
    {
        return $builder->with('user:id,name');
    }

    /**
     * Scope the query to include the comment post and user id/name.
     *
     * @param  Builder<Comment>  $builder
     * @return Builder<Comment>
     */
    public function scopeWithPostAndUserName(Builder $builder): Builder
    {
        return $builder->with(['post', 'user:id,name']);
    }
}
