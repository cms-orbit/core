<?php

namespace CmsOrbit\Core\Http\Requests;

use CmsOrbit\Core\Resources\ResourceRequest;

class ForceDeleteRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->can('forceDelete');
    }
}
