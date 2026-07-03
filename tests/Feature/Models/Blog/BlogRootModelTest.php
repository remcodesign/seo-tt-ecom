<?php

declare(strict_types=1);

use App\Models\Blog\BlogRootModel;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BlogRootModel', function (): void {
    describe('Table Name Resolution', function (): void {
        it('returns the table name as-is when it already starts with blog_', function (): void {
            $model = new #[Table(name: 'blog_custom')]
            class extends BlogRootModel {};

            expect($model->getTable())->toBe('blog_custom');
        });

        it('prepends blog_ prefix and singularizes when table does not start with blog_', function (): void {
            $model = new #[Table(name: 'posts')]
            class extends BlogRootModel {};

            expect($model->getTable())->toBe('blog_post');
        });

        it('handles a table name that is already singular without blog_ prefix', function (): void {
            $model = new #[Table(name: 'category')]
            class extends BlogRootModel {};

            expect($model->getTable())->toBe('blog_category');
        });
    });
});
