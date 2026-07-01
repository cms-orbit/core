<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Filters;

use CmsOrbit\Core\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class DefaultSorted extends Filter
{
    public $parameters = [];

    public $display = false;

    public function __construct(
        protected ?string $sortColumn = null,
        protected string $sortOrder = 'asc'
    ) {
        parent::__construct();
    }

    public function name(): string
    {
        return '';
    }

    public function run(Builder $builder): Builder
    {
        return $builder->defaultSort(
            $this->sortColumn ?? $builder->getModel()->getKeyName(),
            $this->sortOrder
        );
    }

    public function display(): array
    {
        return [];
    }
}
