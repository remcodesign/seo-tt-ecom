<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use Illuminate\Database\Eloquent\Model;

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
