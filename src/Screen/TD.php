<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen;

use CmsOrbit\Core\Screen\Concerns\ComplexFieldConcern;
use CmsOrbit\Core\Screen\Contracts\Fieldable;
use CmsOrbit\Core\Screen\Fields\DateRange;
use CmsOrbit\Core\Screen\Fields\DateTimer;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\NumberRange;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Support\Formats;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TD extends Cell
{
    /**
     * Align the cell to the left.
     */
    public const ALIGN_LEFT = 'start';

    /**
     * Align the cell to the center.
     */
    public const ALIGN_CENTER = 'center';

    /**
     * Align the cell to the right.
     */
    public const ALIGN_RIGHT = 'end';

    public const FILTER_TEXT = 'text';

    public const FILTER_NUMERIC = 'number';

    public const FILTER_DATE = 'date';

    public const FILTER_DATE_RANGE = 'dateRange';

    public const FILTER_NUMBER_RANGE = 'numberRange';

    public const FILTER_SELECT = 'select';

    /**
     * @var string|null|int
     */
    protected $width;

    /**
     * @var string|null
     */
    protected $style;

    /**
     * @var string|null
     */
    protected $class;

    /**
     * @var string
     */
    protected $filter;

    /**
     * @var bool
     */
    protected $sort;

    /**
     * @var string
     */
    protected $align = self::ALIGN_LEFT;

    /**
     * @var int
     */
    protected $colspan = 1;

    /**
     * Displays whether the user can hide
     * or show the column in the browser.
     *
     * @var bool
     */
    protected $allowUserHidden = true;

    /**
     * Should the user independently enable
     * the display of the column.
     *
     * @var bool
     */
    protected $defaultHidden = false;

    /**
     * Render a select column filter as top tabs (Filament-style).
     *
     * @var bool
     */
    protected $filterAsTabs = false;

    /**
     * Render the column filter inline in the table toolbar instead of the popover.
     *
     * @var bool
     */
    protected $filterInline = false;

    /**
     * Possible options for filters if it's select
     *
     * @var array
     */
    protected $filterOptions = [];

    /**
     * Callable return filter value in column
     *
     * @var callable
     */
    protected $callbackFilterValue;

    /**
     * @param string|int $width
     */
    public function width($width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @param string $style
     */
    public function style($style): self
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @param string $class
     */
    public function class($class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @param string|Field $filter
     */
    public function filterOptions(iterable $filterOptions): self
    {
        $this->filterOptions = $filterOptions;

        return $this;
    }

    public function filterValue(callable $callable): self
    {
        $this->callbackFilterValue = $callable;

        return $this;
    }

    /**
     * @param string                 $filter
     * @param iterable|callable|null $options
     */
    public function filter($filter = self::FILTER_TEXT, $options = null): self
    {
        if (is_iterable($options)) {
            $this->filterOptions($options);
        }

        if (is_callable($options)) {
            $this->callbackFilterValue = $options;
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * @return $this
     */
    public function filterAsTabs(bool $tabs = true): self
    {
        $this->filterAsTabs = $tabs;

        return $this;
    }

    /**
     * @return $this
     */
    public function filterInline(bool $inline = true): self
    {
        $this->filterInline = $inline;

        return $this;
    }

    public function sort(bool $sort = true): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @return $this
     */
    public function align(string $align): self
    {
        $this->align = $align;

        return $this;
    }

    /**
     * @return $this
     */
    public function alignLeft(): self
    {
        $this->align = self::ALIGN_LEFT;

        return $this;
    }

    /**
     * @return $this
     */
    public function alignRight(): self
    {
        $this->align = self::ALIGN_RIGHT;

        return $this;
    }

    /**
     * @return $this
     */
    public function alignCenter(): self
    {
        $this->align = self::ALIGN_CENTER;

        return $this;
    }

    /**
     * @return $this
     */
    public function colspan(int $colspan): self
    {
        $this->colspan = $colspan;

        return $this;
    }

    /**
     * Serialize the column to the React JSON contract (ColumnNode).
     *
     * @return array<string, mixed>
     */
    public function toArray($repository = null): array
    {
        $filter = $this->buildFilter();
        $rendered = null;
        $field = null;
        $actions = null;

        if ($this->render !== null && $repository !== null) {
            $value = $this->handler($repository);

            if ($value instanceof Group && collect($value->getGroup())->every(fn ($item) => $item instanceof Action)) {
                $actions = collect($value->getGroup())
                    ->map(fn (Action $action) => $action->toArray())
                    ->filter()
                    ->values()
                    ->all();
            } elseif ($value instanceof Action) {
                $actions = [$value->toArray()];
            } elseif ($value instanceof Fieldable) {
                $field = $value->toArray();
            } else {
                $rendered = $value instanceof Htmlable ? $value->toHtml() : (string) $value;
            }
        } elseif ($repository !== null && $this->shouldAutoFormatDateTime()) {
            $raw = $repository instanceof Model
                ? $repository->getAttribute($this->column)
                : $repository->getContent($this->name);

            if ($raw !== null && $raw !== '') {
                $rendered = Formats::formatDateTimeForTable($raw);
            }
        }

        return [
            'name'            => $this->name,
            'column'          => $this->column,
            'title'           => $this->title,
            'slug'            => $this->sluggable(),
            'align'           => $this->align,
            'width'           => is_numeric($this->width) ? $this->width.'px' : $this->width,
            'sort'            => (bool) ($this->sort ?? false),
            'sortUrl'         => $this->buildSortUrl(),
            'filter'          => $filter?->toArray(),
            'filterString'    => $this->buildFilterString(),
            'filterTabs'      => $this->filterAsTabs,
            'popover'         => $this->popover,
            'defaultHidden'   => $this->defaultHidden,
            'allowUserHidden' => $this->allowUserHidden,
            'colspan'         => $this->colspan,
            'class'           => $this->class,
            'style'           => $this->style,
            'rendered'        => $rendered,
            'field'           => $field,
            'actions'         => $actions,
        ];
    }

    /**
     * Builds a column heading.
     *
     * @return Factory|View
     */
    public function buildTh()
    {
        return view('orbit::partials.layouts.th', [
            'width'        => is_numeric($this->width) ? $this->width.'px' : $this->width,
            'align'        => $this->align,
            'sort'         => $this->sort,
            'sortUrl'      => $this->buildSortUrl(),
            'column'       => $this->column,
            'title'        => $this->title,
            'filter'       => $this->buildFilter(),
            'filterString' => $this->buildFilterString(),
            'slug'         => $this->sluggable(),
            'popover'      => $this->popover,
        ]);
    }

    protected function buildFilter(): ?Field
    {
        /** @var Field $filter|string */
        $filter = $this->filter;

        if ($filter === null) {
            return null;
        }

        if (is_string($filter)) {
            $filter = $this->detectConstantFilter($filter);
        }

        $built = $filter->name("filter[$this->column]")
            ->placeholder($this->title ?: __('Filter'))
            ->form('filters')
            ->value(normalize_filter_values(get_filter($this->column)))
            ->set('inline', $this->filterInline)
            ->set('compact', true)
            ->autofocus();

        if (! $this->filterInline) {
            $built->title($this->title);
        }

        if ($built instanceof Select) {
            $built->allowEmpty();

            if ($this->filterInline) {
                $built->set('searchable', true);
            }
        }

        return $built;
    }

    protected function detectConstantFilter(string $filter): Field
    {
        $input = match ($filter) {
            self::FILTER_DATE_RANGE   => DateRange::make()->disableMobile()->set('layout', 'stack'),
            self::FILTER_NUMBER_RANGE => NumberRange::make(),
            self::FILTER_SELECT       => $this->buildSelectFilter(),
            self::FILTER_DATE         => DateTimer::make()->inline()->format('Y-m-d'),
            default                   => Input::make()->type($filter),
        };

        return $input;
    }

    protected function buildSelectFilter(): Select
    {
        $select = Select::make()->options($this->filterOptions);

        if (! $this->filterAsTabs) {
            $select->multiple();
        }

        return $select;
    }

    /**
     * Builds content for the column.
     *
     * @param Repository|Model $repository
     *
     * @return Factory|View
     */
    public function buildTd($repository, ?object $loop = null)
    {
        $value = $this->render ? $this->handler($repository, $loop) : $repository->getContent($this->name);

        return view('orbit::partials.layouts.td', [
            'align'   => $this->align,
            'value'   => $value,
            'render'  => $this->render,
            'slug'    => $this->sluggable(),
            'width'   => is_numeric($this->width) ? $this->width.'px' : $this->width,
            'style'   => $this->style,
            'class'   => $this->class,
            'colspan' => $this->colspan,
        ]);
    }

    public function isAllowUserHidden(): bool
    {
        return $this->allowUserHidden;
    }

    /**
     * Builds an item menu for show/hiden column.
     *
     * @return Factory|View|null
     */
    public function buildItemMenu()
    {
        if (! $this->isAllowUserHidden()) {
            return;
        }

        return view('orbit::partials.layouts.selectedTd', [
            'title'         => $this->title,
            'slug'          => $this->sluggable(),
            'defaultHidden' => var_export($this->defaultHidden, true),
        ]);
    }

    protected function sluggable(): string
    {
        return Str::slug($this->name);
    }

    protected function shouldAutoFormatDateTime(): bool
    {
        if ($this->render !== null) {
            return false;
        }

        return Str::endsWith($this->column, '_at')
            || Str::endsWith($this->column, '_date')
            || $this->filter === self::FILTER_DATE;
    }

    /**
     * Prevents the user from hiding a column in the interface.
     */
    public function cantHide(bool $hidden = false): self
    {
        $this->allowUserHidden = $hidden;

        return $this;
    }

    /**
     * @return $this
     */
    public function defaultHidden(bool $hidden = true): self
    {
        $this->defaultHidden = $hidden;

        return $this;
    }

    public function buildSortUrl(): string
    {
        $query = request()->collect()->put('sort', revert_sort($this->column))->toArray();

        return url()->current().'?'.http_build_query($query);
    }

    /**
     * @param TD[] $columns
     */
    public static function isShowVisibleColumns($columns): bool
    {
        return collect($columns)->filter(fn ($column) => $column->isAllowUserHidden())->isNotEmpty();
    }

    /**
     * @deprecated is not usage
     *
     * Decides whether a filter can be provided with a complex (array-like) value, or it needs a scalar one.
     */
    protected function isComplexFieldType(Field $field): bool
    {
        return $field instanceof ComplexFieldConcern;
    }

    protected function buildFilterString(): ?string
    {
        $filter = normalize_filter_values(get_filter($this->column));

        if ($filter === null) {
            return null;
        }

        if ($this->callbackFilterValue !== null) {
            return call_user_func($this->callbackFilterValue, $filter);
        }

        if (is_array($filter)) {
            if (isset($filter['start']) || isset($filter['end'])) {
                return ($filter['start'] ?? '').' - '.($filter['end'] ?? '');
            }

            if ($this->filterOptions) {
                $filter = array_map(
                    fn ($val) => $this->filterOptions[$val] ?? $val,
                    $filter,
                );
            }

            return implode(', ', $filter);
        }

        return (string) $filter;
    }
}
