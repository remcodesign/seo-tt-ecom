<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\Blog\Posts\Form;
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
