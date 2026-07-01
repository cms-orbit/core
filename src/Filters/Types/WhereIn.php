<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Filters\Types;

use CmsOrbit\Core\Filters\BaseHttpEloquentFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WhereIn extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $query = $this->getHttpValue();

        $value = is_array($query) ? $query : Str::of($query)->explode(',');

        return $builder->whereIn($this->column, $value);
    }
}
