<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Filters\Types;

use CmsOrbit\Core\Filters\BaseHttpEloquentFilter;
use Illuminate\Database\Eloquent\Builder;

class WhereBetween extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        return $builder->whereBetween($this->column, $this->getHttpValue());
    }
}
