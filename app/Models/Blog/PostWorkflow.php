<?php

declare(strict_types=1);

namespace App\Models\Blog;

use App\Enums\PostWorkflowStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $post_id
 * @property string $file_hash
 * @property PostWorkflowStatus $status
 * @property Carbon|null $captured_at
 * @property string|null $embedding
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Post $post
 */
#[Fillable(['post_id', 'file_hash', 'status', 'captured_at', 'embedding'])]
#[Table(name: 'blog_post_workflows')]
class PostWorkflow extends BlogRootModel
{
    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => PostWorkflowStatus::class,
            'captured_at' => 'immutable_datetime',
        ];
    }
}
