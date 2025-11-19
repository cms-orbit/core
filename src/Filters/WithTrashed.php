<?php

namespace CmsOrbit\Core\Filters;

use Illuminate\Database\Eloquent\Builder;
use CmsOrbit\Core\Filters\Filter;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\CheckBox;

class WithTrashed extends Filter
{
    /**
     * @var array
     */
    public $parameters = [
        'withTrashed',
    ];

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Deleted entries';
    }

    /**
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        return $builder->onlyTrashed();
    }

    /**
     * @return Field[]
     */
    public function display(): array
    {
        return [
            CheckBox::make('withTrashed')
                ->value($this->request->boolean('withTrashed'))
                ->placeholder(__('Show deleted entries')),
        ];
    }
}
