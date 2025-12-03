<?php

namespace CmsOrbit\Core\Http\Requests;

use CmsOrbit\Core\ResourceRequest;

class ViewRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->can('view');
    }
}
