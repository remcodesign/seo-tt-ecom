<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Blog\Post;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Post> $posts
 */
#[Fillable(['name', 'slug'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    #[\Override]
    protected static function booted(): void
    {
        // Prevent deletion of the "[Uncategorized]" category
        static::deleting(function (Category $category): void {
            if ($category->slug === 'uncategorized') {
                throw new \RuntimeException('The "[Uncategorized]" category is protected and cannot be deleted.');
            }
        });
    }

    /**
     * @return MorphToMany<Post, $this>
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'categorizable')->withTimestamps();
    }
}
