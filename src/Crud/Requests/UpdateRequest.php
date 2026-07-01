<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Requests;

class UpdateRequest extends CreateRequest
{
    public function authorize(): bool
    {
        return $this->can('update');
    }
}
