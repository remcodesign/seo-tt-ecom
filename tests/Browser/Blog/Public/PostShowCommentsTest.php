<?php

declare(strict_types=1);

use App\Models\Blog\Comment;
use App\Models\Blog\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createPostUser(): array
{
    $user = User::factory()->create(
        [
            'email' => 'post-owner@example.com',
        ]
    );
    $post = Post::factory()->for($user)->create(
        [
            'title' => 'Test Post',
            'slug' => 'test-post',
        ]
    );

    return [$user, $post];
}

function createCommentUsers(): array
{
    $owner = User::factory()->create(
        [
            'email' => 'comment-owner@example.com',
        ]
    );
    $otherUser = User::factory()->create(
        [
            'email' => 'comment-other@example.com',
        ]
    );

    return [$owner, $otherUser];
}

function createCommentsForPost(Post $post, User $owner, User $otherUser): array
{
    return [
        Comment::factory()->for($post)->for($owner)->create(['comment' => 'Owner comment']),
        Comment::factory()->for($post)->for($otherUser)->create(['comment' => 'Other comment']),
    ];
}

function loginUserViaModal(
    PendingAwaitablePage|Webpage|AwaitableWebpage $page,
    User $user
): PendingAwaitablePage|AwaitableWebpage|Webpage {
    return $page
        ->assertCount('[data-test="nav-login-button"]', 1)
        ->assertSeeIn('[data-test="nav-login-button"]', 'Login')
        ->click('[data-test="nav-login-button"]')

        ->fill('[data-test="login-email-input"]', $user->email)
        ->fill('[data-test="login-password-input"]', 'password')
        ->click('[data-test="login-submit-button"]')

        ->wait(1)

        ->assertSeeIn('[data-test="account-menu-button"]', $user->name);
}

// ----------
// ->screenshot(true, '1-post-show-comments-guest-view');
// ->screenshotElement('[data-test="nav-login-button"]', 'login-button-screenshot')
// ----------

it('1. does not show comment CRUD controls to guests on the post page', function (): void {
    [$postUser, $post] = createPostUser();
    [$commentOwner, $commentOtherUser] = createCommentUsers();
    [$ownerComment, $otherComment] = createCommentsForPost($post, $commentOwner, $commentOtherUser);

    $pendingAwaitablePage = visit('/blog/posts/'.$post->slug);

    // test assertions
    $pendingAwaitablePage
        ->assertSee('Test Post')
        ->assertSeeIn('[data-test="post-user-name"]', $postUser->name);

    $pendingAwaitablePage
        ->assertSee('Comments')
        ->assertSee('2 comments')
        ->assertSee('Owner comment')
        ->assertSee('Other comment')

        ->assertCount('[data-test="comment-edit-button"]', 0)
        ->assertCount('[data-test="comment-delete-button"]', 0)

        ->assertNoJavaScriptErrors();
});

it('2. does show comment CRUD controls to logged-in users on the post page and not to non-owners of comments', function (): void {
    [$postUser, $post] = createPostUser();
    [$commentOwner, $commentOtherUser] = createCommentUsers();
    [$ownerComment, $otherComment] = createCommentsForPost($post, $commentOwner, $commentOtherUser);

    $page = visit('/blog/posts/'.$post->slug);

    // test assertions
    $page
        ->assertSee('Test Post');

    $page = loginUserViaModal($page, $commentOwner);

    $page
        ->assertCount('[data-test="comment-edit-button"]', 1)
        ->assertCount('[data-test="comment-delete-button"]', 1)

        ->assertNoJavaScriptErrors();
});

it('3. allows an authenticated user to create, update, delete, and read comments on the post page', function (): void {
    [$postUser, $post] = createPostUser();
    [$commentOwner, $commentOtherUser] = createCommentUsers();
    [$ownerComment, $otherComment] = createCommentsForPost($post, $commentOwner, $commentOtherUser);

    $page = visit('/blog/posts/'.$post->slug);

    // test assertions
    $page
        ->assertSee('Test Post');

    $page = loginUserViaModal($page, $commentOwner);

    $myNewUpdatedComment = 'My new updated comment';
    $myNewCreatedComment = 'My new created comment';

    $page
        // UPDATE COMMENT
        ->assertCount('[data-test="comment-edit-button"]', 1)
        ->click('[data-test="comment-edit-button"]')

        ->assertValue('[data-test="comment-edit-textarea"]', $ownerComment->comment)
        ->fill('[data-test="comment-edit-textarea"]', $myNewUpdatedComment)
        ->assertValue('[data-test="comment-edit-textarea"]', $myNewUpdatedComment)
        ->click('[data-test="comment-save-button"]')
        ->wait(1)

        ->assertSee($myNewUpdatedComment)

        // DELETE COMMENT
        ->assertCount('[data-test="comment-delete-button"]', 1)
        ->click('[data-test="comment-delete-button"]')
        // confirm delete modal
        ->assertSee('Delete')
        ->click('[data-test="confirm-button"]')
        ->wait(1)

        ->assertDontSee($myNewCreatedComment)
        ->assertSee($otherComment->comment)

        // CREATE COMMENT
        ->fill('[data-test="comment-input"]', $myNewCreatedComment)
        ->click('[data-test="comment-submit-button"]')
        ->wait(1)

        ->assertSee($myNewCreatedComment)
        ->assertSee($otherComment->comment)
        ->assertSee('2 comments')
        ->assertCount('[data-test="comment-edit-button"]', 1)
        ->assertCount('[data-test="comment-delete-button"]', 1)

        ->assertNoJavaScriptErrors();
});
