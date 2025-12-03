<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Types;

use Illuminate\Database\Eloquent\Builder;
use CmsOrbit\Core\Foundation\Filters\BaseHttpEloquentFilter;

class Ilike extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        return $builder->where(
            $this->column,
            'ILIKE',
            '%'.$this->getHttpValue().'%'
        );
    }
}
