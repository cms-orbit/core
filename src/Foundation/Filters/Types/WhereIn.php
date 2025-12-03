<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Types;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use CmsOrbit\Core\Foundation\Filters\BaseHttpEloquentFilter;

class WhereIn extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $query = $this->getHttpValue();

        $value = is_array($query) ? $query : Str::of($query)->explode(',');

        return $builder->whereIn($this->column, $value);
    }
}
