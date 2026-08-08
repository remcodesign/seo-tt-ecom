<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Traits\Stubs;

use App\Http\Controllers\Api\Traits\HasOptionalIncludes;

// This stub class is used to test the HasOptionalIncludes trait's default allowedIncludes behavior.
class HasOptionalIncludesDefaultAllowedIncludesStub
{
    use HasOptionalIncludes;

    public function requestIncludedRelationsPublic(): array
    {
        return $this->requestIncludedRelations();
    }
}
