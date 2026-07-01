<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Filters\Types;

use CmsOrbit\Core\Filters\BaseHttpEloquentFilter;
use Illuminate\Database\Eloquent\Builder;

class WhereMaxMin extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $value = $this->getHttpValue();

        $builder->when($value['min'] ?? null, fn (Builder $query) => $query->where($this->column, '>=', $value['min']));
        $builder->when($value['max'] ?? null, fn (Builder $query) => $query->where($this->column, '<=', $value['max']));

        return $builder;
    }
}
