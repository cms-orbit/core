<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics\Filters;

use CmsOrbit\Core\Filters\Filter;
use CmsOrbit\Core\Screen\Fields\Select;
use Illuminate\Database\Eloquent\Builder;

class VisitorRecordTableFilter extends Filter
{
    /**
     * @var array<int, string>|null
     */
    public $parameters = ['filter.audience', 'filter.scope'];

    public function run(Builder $builder): Builder
    {
        $builder = match ($this->request->input('filter.audience')) {
            'guests'  => $builder->whereNull('user_id'),
            'members' => $builder->whereNotNull('user_id'),
            default   => $builder,
        };

        return match ($this->request->input('filter.scope')) {
            'host'     => $builder->whereNull('instance_id'),
            'instance' => $builder->whereNotNull('instance_id'),
            default    => $builder,
        };
    }

    public function display(): iterable
    {
        yield Select::make('filter.audience')
            ->title(__('Audience'))
            ->options([
                ''        => __('All visits'),
                'guests'  => __('Guests only'),
                'members' => __('Signed-in users'),
            ]);

        yield Select::make('filter.scope')
            ->title(__('Scope'))
            ->options([
                ''         => __('All scopes'),
                'host'     => __('Host'),
                'instance' => __('Instance'),
            ]);
    }
}
