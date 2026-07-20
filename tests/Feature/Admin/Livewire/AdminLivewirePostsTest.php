<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\Blog\Posts\Form;
use App\Livewire\Admin\Blog\Posts\Index;
use App\Livewire\Components\Blog\Posts\CommentLister;
use App\Livewire\Components\Blog\Posts\FilterSearch;
use App\Models\Blog\Post;
use App\Models\User;
use Livewire\Livewire;

describe('Livewire admin blog posts', function (): void {
    describe('Index :: Sorting/Search + Buttons', function (): void {
        it('renders the index lister with posts', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Post::factory()->count(3)->for($writer)->create();

            Livewire::test(Index::class)
                ->assertSuccessful()
                ->assertSee('All blog posts')
                ->assertSee(Post::first()->title);
        });

        it('paginates the posts', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Post::factory()->count(15)->for($writer)->create();

            // pagination set to 8 per page, so first page should show first 8 posts, second page should show next 7 posts
            Livewire::test(Index::class)
                ->assertSuccessful()
                ->assertSee('All blog posts')
                ->assertSee(Post::first()->title)
                ->assertSee(Post::skip(7)->first()->title)
                ->assertDontSee(Post::skip(8)->first()->title)
                ->call('gotoPage', 2)
                ->assertSee(Post::skip(8)->first()->title);
        });

        it('has edit button for each post', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Post::factory()->count(3)->for($writer)->create();

            Livewire::test(Index::class)
                ->assertSuccessful()
                ->assertSee('All blog posts')
                ->assertSee(Post::first()->title)
                ->assertSee(route('admin.blog.posts.edit', Post::first()->id));
        });

        it('has delete button for each post', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Post::factory()->count(3)->for($writer)->create();

            Livewire::test(Index::class)
                ->assertSuccessful()
                ->assertSee('All blog posts')
                ->assertSee(Post::first()->title)
                ->assertSee(route('admin.blog.posts.edit', Post::first()->id))
                ->assertSeeHtml('wire:click="delete('.Post::first()->id.')"');
        });

        it('searches posts by title', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Post::factory()->count(3)->for($writer)->create();

            $postToSearch = Post::first();

            Livewire::test(Index::class)
                ->set('search', $postToSearch->title)
                ->call('setSearch', $postToSearch->title)
                ->assertSee($postToSearch->title)
                ->assertDontSee(Post::where('id', '!=', $postToSearch->id)->first()->title);
        });

        it('dispatches searchUpdated when the search input changes', function (): void {
            Livewire::test(FilterSearch::class)
                ->set('search', 'test')
                ->assertDispatched('searchUpdated', 'test');
        });

        it('sorts posts by title', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Post::factory()->count(3)->for($writer)->create();

            Livewire::test(Index::class)
                ->call('sortBy', 'title')
                ->assertSet('orderBy', 'title')
                ->assertSet('orderDirection', 'asc')
                ->assertSeeInOrder([
                    Post::orderBy('title', 'asc')->first()->title,
                    Post::orderBy('title', 'asc')->skip(1)->first()->title,
                    Post::orderBy('title', 'asc')->skip(2)->first()->title,
                ])
                ->call('sortBy', 'title')
                ->assertSet('orderBy', 'title')
                ->assertSet('orderDirection', 'desc')
                ->assertSeeInOrder([
                    Post::orderBy('title', 'desc')->first()->title,
                    Post::orderBy('title', 'desc')->skip(1)->first()->title,
                    Post::orderBy('title', 'desc')->skip(2)->first()->title,
                ]);
        });

        it('ignores invalid sort columns', function (): void {
            Livewire::test(Index::class)
                ->call('sortBy', 'invalid_column')
                ->assertSet('orderBy', 'created_at')
                ->assertSet('orderDirection', 'desc');
        });
    });

    describe('Delete', function (): void {
        it('deletes a post', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $postToDelete = Post::factory()->for($writer)->create();

            Livewire::test(Index::class)
                ->call('delete', $postToDelete->id);

            expect(Post::find($postToDelete->id))->toBeNull();
        });
    });

    describe('Create', function (): void {
        it('creates a new post without published_on', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            Livewire::test(Form::class)
                ->set('form.title', 'New Post Title')
                ->set('form.content', 'New Post Content')
                ->set('form.user_id', $writer->id)
                ->call('save')
                ->assertRedirectToRoute('admin.blog.posts.index');

            expect(Post::where('title', 'New Post Title')->exists())->toBeTrue();
        });

        it('creates a new post with published_on', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $publishedOn = now()->addDay()->toDateString();

            Livewire::test(Form::class)
                ->set('form.title', 'New Post Title')
                ->set('form.content', 'New Post Content')
                ->set('form.user_id', $writer->id)
                ->set('form.published_on', $publishedOn)
                ->call('save')
                ->assertRedirectToRoute('admin.blog.posts.index');

            expect(Post::where('title', 'New Post Title')->exists())->toBeTrue();
            expect(Post::where('title', 'New Post Title')->first()->published_on?->toDateString())->toBe($publishedOn);
        });
    });

    describe('Edit', function (): void {
        it('renders the edit post form with existing data', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()->for($writer)->create([
                'title' => 'Original Title',
                'body' => 'Original Body',
                'published_on' => now()->subDay()->toDateString(),
            ]);

            Livewire::test(Form::class, ['post' => $post])
                ->assertSuccessful()
                ->assertSet('form.title', 'Original Title')
                ->assertSet('form.body', 'Original Body')
                ->assertSet('form.published_on', now()->subDay()->toDateString());
        });

        it('shows the slug but does not allow editing of the slug', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()->for($writer)->create([
                'title' => 'Original Title',
                'body' => 'Original Body',
                'published_on' => now()->subDay()->toDateString(),
            ]);

            Livewire::test(Form::class, ['post' => $post])
                ->assertSee($post->slug)
                ->assertSee('(Not editable)');
        });

        it('shows the `View on frontend` link only if the post has a slug and is published', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()->for($writer)->create([
                'title' => 'Original Title',
                'body' => 'Original Body',
                'published_on' => now()->subDay()->toDateString(),
            ]);

            Livewire::test(Form::class, ['post' => $post])
                ->assertSee('View on frontend')
                // the frontend uses Vue3 with it's own router, we can directly link,
                // we can check the format of the link, but we cannot check the actual URL because it is not in the web.php routes
                ->assertSee('View on frontend')
                ->assertSeeHtml('href="/blog/posts/'.$post->slug.'"');

            // if the post is not published, the link should not be visible
            $post->update(['published_on' => null]);

            Livewire::test(Form::class, ['post' => $post])
                ->assertDontSee('View on frontend')
                ->assertSee('Post is not published');
        });

        it('updates a post and redirects back to the list', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $newWriter = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()->for($writer)->create([
                'title' => 'Original Title',
                'body' => 'Original Body',
                'published_on' => now()->subDay()->toDateString(),
            ]);

            $newPublishedOn = now()->addDay()->toDateString();

            Livewire::test(Form::class, ['post' => $post])
                ->set('form.title', 'Updated Title')
                ->set('form.body', 'Updated Body')
                ->set('form.user_id', $newWriter->id)
                ->set('form.published_on', $newPublishedOn)
                ->call('save')
                ->assertRedirectToRoute('admin.blog.posts.index');

            $post->refresh();

            expect($post->title)->toBe('Updated Title');
            expect($post->body)->toBe('Updated Body');
            expect($post->user_id)->toBe($newWriter->id);
            expect($post->published_on?->toDateString())->toBe($newPublishedOn);
        });

        it('renders the edit post form with published_on prefilled', function (): void {
            $writer = User::factory()->create();

            $post = Post::factory()
                ->for($writer)
                ->create([
                    'published_on' => now()->subDay()->toDateString(),
                ]);

            Livewire::test(Form::class, ['post' => $post])
                ->assertSuccessful()
                ->assertSet('form.published_on', $post->published_on?->toDateString());
        });

        it('persists an edited published_on date', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()
                ->for($writer)
                ->create([
                    'published_on' => now()->subDays(2)->toDateString(),
                ]);

            $newPublishedOn = now()->addDay()->toDateString();

            Livewire::test(Form::class, ['post' => $post])
                ->set('form.title', 'Updated Title')
                ->set('form.published_on', $newPublishedOn)
                ->call('save')
                ->assertRedirectToRoute('admin.blog.posts.index');

            expect($post->fresh()->published_on?->toDateString())->toBe($newPublishedOn);
        });

        it('clears published_on when the date input is emptied', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()
                ->for($writer)
                ->create([
                    'published_on' => now()->subDays(5)->toDateString(),
                ]);

            Livewire::test(Form::class, ['post' => $post])
                ->set('form.published_on', '')
                ->call('save')
                ->assertRedirectToRoute('admin.blog.posts.index');

            expect($post->fresh()->published_on)->toBeNull();
        });
    });

    describe('Edit :: Comments', function (): void {
        it('renders the post comments on the edit page', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()
                ->for($writer)
                ->hasComments(3)
                ->create();

            Livewire::test(Form::class, ['post' => $post])
                ->assertSuccessful()
                ->assertSee('Comments')
                ->assertSee($post->comments()->first()->body);
        });

        it('deletes a comment from the post edit page', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()
                ->for($writer)
                ->hasComments(1)
                ->create();

            // using the CommentLister component to delete the comment
            Livewire::test(CommentLister::class, ['post' => $post])
                ->call('delete', $post->comments()->first()->id)
                ->assertSuccessful();
        });

        it('cannot delete a comment that does not belong to the post', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $otherPost = Post::factory()
                ->for($writer)
                ->hasComments(1)
                ->create();

            $post = Post::factory()
                ->for($writer)
                ->hasComments(1)
                ->create();

            // using the CommentLister component to delete the comment
            Livewire::test(CommentLister::class, ['post' => $post])
                ->call('delete', $otherPost->comments()->first()->id)
                ->assertSee('Cannot delete comment: Comment does not exist.');
        });

        // cannot delete a comment if the post does not exist
        it('cannot delete a comment if the post does not exist', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
            ]);

            $post = Post::factory()
                ->for($writer)
                ->hasComments(1)
                ->create();

            $commentId = $post->comments()->first()->id;
            $post->delete();

            // using the CommentLister component to delete the comment
            Livewire::test(CommentLister::class, ['post' => $post])
                ->call('delete', $commentId)
                ->assertSee('Cannot delete comment: Post does not exist.');
        });
    });
});
