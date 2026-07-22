<?php

declare(strict_types=1);

namespace App\Models\Blog;

use App\Models\Category;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\Blog\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $body
 * @property string $slug
 * @property CarbonImmutable|null $published_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Comment> $comments
 *
 * @method static Builder<static> withoutContentFields()
 * @method static Builder<static> published()
 */
#[Fillable(['user_id', 'title', 'body', 'slug', 'published_on'])]
#[Table(name: 'blog_posts')]
class Post extends BlogRootModel
{
    /**
     * @use HasFactory<PostFactory>
     */
    use HasFactory;

    /**
     * Get the model's attribute type casts.
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            // Laravel 10.0+ supports `immutable_datetime` for CarbonImmutable support
            'published_on' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return MorphToMany<Category, $this>
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable')->withTimestamps();
    }

    /**
     * Scope to only return published posts.
     *
     * @param  Builder<Post>  $builder
     * @return Builder<Post>
     */
    public function scopePublished(Builder $builder): Builder
    {
        return $builder->whereNotNull('published_on');
    }

    /**
     * Scope to exclude content-heavy columns (body, future images, etc.)
     * when only lightweight post data is needed (e.g. in comment listings).
     *
     * @param  Builder<Post>  $builder
     * @return Builder<Post>
     */
    public function scopeWithoutContentFields(Builder $builder): Builder
    {
        return $builder->select(['id', 'user_id', 'title', 'slug', 'published_on', 'created_at', 'updated_at']);
    }
}
