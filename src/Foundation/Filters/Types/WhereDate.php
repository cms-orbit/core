<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Types;

use Illuminate\Database\Eloquent\Builder;
use CmsOrbit\Core\Foundation\Filters\BaseHttpEloquentFilter;

class WhereDate extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        return $builder->whereDate($this->column, $this->getHttpValue());
    }
}
