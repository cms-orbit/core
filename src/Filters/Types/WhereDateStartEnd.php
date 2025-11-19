<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Types;

use Illuminate\Database\Eloquent\Builder;
use CmsOrbit\Core\Filters\BaseHttpEloquentFilter;

class WhereDateStartEnd extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $value = $this->getHttpValue();

        $builder->when($value['start'] ?? null, fn (Builder $query) => $query->whereDate($this->column, '>=', $value['start']));
        $builder->when($value['end'] ?? null, fn (Builder $query) => $query->whereDate($this->column, '<=', $value['end']));

        return $builder;
    }
}
