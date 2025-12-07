<?php

namespace CmsOrbit\Core\Http\Requests;

use CmsOrbit\Core\Resources\Layouts\ResourceFields;
use CmsOrbit\Core\Resources\ResourceRequest;

class CreateRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->can('create');
    }

    /**
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'GET') {
            return [];
        }

        $model = $this->findModel() ?? $this->resource()->getModel();
        $rules = $this->resource()->rules($model);

        return collect($rules)
            ->mapWithKeys(function ($value, $key) {
                return [ResourceFields::PREFIX . '.' . $key => $value];
            })
            ->toArray();
    }
}
