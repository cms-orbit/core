<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Types;

use Illuminate\Database\Eloquent\Builder;
use CmsOrbit\Core\Filters\BaseHttpEloquentFilter;

class Where extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        return $builder->where($this->column, $this->getHttpValue());
    }
}
