<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['post_id', 'user_id', 'comment'])]
class Comment extends Model
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
        return $this->belongsTo(Post::class);
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
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeWithUserName(Builder $query): Builder
    {
        return $query->with('user:id,name');
    }

    /**
     * Scope the query to include the comment post and user id/name.
     *
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeWithPostAndUserName(Builder $query): Builder
    {
        return $query->with(['post', 'user:id,name']);
    }
}
