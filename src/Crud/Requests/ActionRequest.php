<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Requests;

use Illuminate\Support\Collection;

class ActionRequest extends EntityRequest
{
    public function rules(): array
    {
        return [
            '_action' => 'required',
        ];
    }

    public function withValidator($validator): void
    {
        // Action requests operate on the raw payload (selected model ids).
    }

    /**
     * The set of models the action should run against.
     */
    public function models(): Collection
    {
        $models = collect();

        if ($this->has('_models')) {
            $models = $this->getModelQuery()->findMany($this->get('_models'));
        }

        $current = $this->findModel();

        if ($current !== null) {
            $models->push($current);
        }

        return $models;
    }
}
