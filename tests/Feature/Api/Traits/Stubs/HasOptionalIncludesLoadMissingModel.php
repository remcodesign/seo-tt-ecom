<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use Illuminate\Database\Eloquent\Model;

// This stub class is used to test the HasOptionalIncludes trait's loadIncludes method when the model is missing the requested relations.
class HasOptionalIncludesLoadMissingModel extends Model
{
    public array $loadedIncludes = [];

    #[\Override]
    public function loadMissing($relations): self
    {
        $this->loadedIncludes = is_array($relations) ? $relations : [$relations];

        return $this;
    }
}
