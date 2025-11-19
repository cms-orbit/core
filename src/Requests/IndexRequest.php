<?php

namespace CmsOrbit\Core\Requests;

use CmsOrbit\Core\Crud\ResourceRequest;

class IndexRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->can('viewAny');
    }
}
