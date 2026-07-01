<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Requests;

class ForceDeleteRequest extends EntityRequest
{
    public function authorize(): bool
    {
        return $this->can('forceDelete');
    }
}
