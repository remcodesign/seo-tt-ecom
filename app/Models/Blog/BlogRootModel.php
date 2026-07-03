<?php

declare(strict_types=1);

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BlogRootModel extends Model
{
    #[\Override]
    public function getTable(): string
    {
        $table = parent::getTable();

        if (Str::startsWith($table, 'blog_')) {
            return $table;
        }

        return 'blog_'.Str::singular($table);
    }
}
