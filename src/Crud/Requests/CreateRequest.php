<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Requests;

use CmsOrbit\Core\Crud\Layouts\ResourceFields;

class CreateRequest extends EntityRequest
{
    public function authorize(): bool
    {
        return $this->can('create');
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        $model = $this->findModel() ?? $this->entity()->newModel();

        return collect($this->entity()->rules($model))
            ->mapWithKeys(fn ($value, $key) => [ResourceFields::PREFIX.'.'.$key => $value])
            ->all();
    }
}
