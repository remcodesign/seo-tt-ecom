<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits;

use App\Http\Controllers\Api\Traits\HasOptionalIncludes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class HasOptionalIncludesTest extends TestCase
{
    use HasOptionalIncludes;
    use RefreshDatabase;

    protected function allowedIncludes(): array
    {
        return ['user', 'post'];
    }

    public function test_requested_includes_are_filtered_against_allowed_list(): void
    {
        $request = Request::create('/?include=user,invalid,post', 'GET');
        $this->app['request'] = $request;

        $includes = $this->requestIncludedRelations();

        $this->assertSame(['user', 'post'], $includes);
    }

    public function test_request_included_relations_returns_empty_when_no_query_is_present(): void
    {
        $request = Request::create('/', 'GET');
        $this->app['request'] = $request;

        $includes = $this->requestIncludedRelations();

        $this->assertSame([], $includes);
    }

    public function test_request_included_relations_returns_empty_when_no_allowed_includes_are_defined(): void
    {
        $request = Request::create('/?include=user,post', 'GET');
        $this->app['request'] = $request;

        $stub = new class
        {
            use HasOptionalIncludes;

            public function requestIncludedRelationsPublic(): array
            {
                return $this->requestIncludedRelations();
            }
        };

        $includes = $stub->requestIncludedRelationsPublic();

        $this->assertSame([], $includes);
    }

    // note : currently not implemented, but could be useful in the future
    // public function test_load_and_apply_includes_handle_empty_relations_and_load_requested_relations(): void
    // {
    //     $post = Post::factory()->for(User::factory()->create())->create();
    //     $postDataResponse = PostDataResponse::from($post);

    //     $this->assertSame($post, $this->loadIncludes($post, []));

    //     $ref = $postDataResponse;
    //     $this->applyIncludes($postDataResponse, []);
    //     $this->assertSame($ref, $postDataResponse);

    //     $model = $this->loadIncludes($post, ['user']);

    //     $this->assertTrue($model->relationLoaded('user'));
    //     $this->assertSame($post->getRelation('user')->id, $model->user->id);
    // }
}
