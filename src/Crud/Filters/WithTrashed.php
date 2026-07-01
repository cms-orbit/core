<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Filters;

use CmsOrbit\Core\Filters\Filter;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use Illuminate\Database\Eloquent\Builder;

class WithTrashed extends Filter
{
    public $parameters = [
        'withTrashed',
    ];

    public function name(): string
    {
        return __('Deleted entries');
    }

    public function run(Builder $builder): Builder
    {
        return $builder->onlyTrashed();
    }

    public function display(): array
    {
        return [
            CheckBox::make('withTrashed')
                ->value($this->request->boolean('withTrashed'))
                ->placeholder(__('Show deleted entries')),
        ];
    }
}
