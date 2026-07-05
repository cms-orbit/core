<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Filters\Types;

use CmsOrbit\Core\Filters\BaseHttpEloquentFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WhereIn extends BaseHttpEloquentFilter
{
    public function run(Builder $builder): Builder
    {
        $query = $this->getHttpValue();

        $value = is_array($query)
            ? Arr::flatten($query)
            : Str::of($query)->explode(',');

        $value = collect($value)
            ->filter(static fn ($item) => $item !== null && $item !== '')
            ->values()
            ->all();

        if ($value === []) {
            return $builder;
        }

        return $builder->whereIn($this->column, $value);
    }
}
