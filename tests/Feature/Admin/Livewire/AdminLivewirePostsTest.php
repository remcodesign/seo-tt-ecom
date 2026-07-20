<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\Blog\Posts\Form;
use App\Livewire\Admin\Blog\Posts\Index;
use App\Models\Blog\Post;
use App\Models\User;
use Livewire\Livewire;

describe('Livewire admin blog posts', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($this->admin);
    });

    describe('Index Index Sorting/Search + Buttons', function (): void {
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

        // sort on non allowable column should not change orderBy or orderDirection
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

    // edit page+
    describe('Handling published_on on edit', function (): void {
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
});
