<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Facades;

use Illuminate\Support\Facades\Facade;
use CmsOrbit\Core\UI\Layout as BaseLayout;
use CmsOrbit\Core\UI\LayoutFactory;
use CmsOrbit\Core\UI\Layouts\Accordion;
use CmsOrbit\Core\UI\Layouts\Blank;
use CmsOrbit\Core\UI\Layouts\Block;
use CmsOrbit\Core\UI\Layouts\Browsing;
use CmsOrbit\Core\UI\Layouts\Chart;
use CmsOrbit\Core\UI\Layouts\Columns;
use CmsOrbit\Core\UI\Layouts\Component;
use CmsOrbit\Core\UI\Layouts\Legend;
use CmsOrbit\Core\UI\Layouts\Metric;
use CmsOrbit\Core\UI\Layouts\Modal;
use CmsOrbit\Core\UI\Layouts\Rows;
use CmsOrbit\Core\UI\Layouts\Selection;
use CmsOrbit\Core\UI\Layouts\Sortable;
use CmsOrbit\Core\UI\Layouts\Split;
use CmsOrbit\Core\UI\Layouts\Table;
use CmsOrbit\Core\UI\Layouts\Tabs;
use CmsOrbit\Core\UI\Layouts\View;
use CmsOrbit\Core\UI\Layouts\Wrapper;

/**
 * Class Layout.
 *
 * This class provides a static interface for creating layouts.
 * Are defined as classes within the Orbit\Screen\Layouts namespace.
 *
 * @method static View      view(string $view, array $data = [])                    Creates a new view layout with the given data.
 * @method static Component component(string $component)                            Creates a new component layout with the given component.
 * @method static Rows      rows(array $fields)                                     Creates a new rows layout with the given fields.
 * @method static Table     table(string $target, array $columns)                   Creates a new table layout with the given target and columns.
 * @method static Columns   columns(BaseLayout[]|string[] $layouts)                 Creates a new columns layout with the given layout data.
 * @method static Tabs      tabs(BaseLayout[] $layouts)                             Creates a new tabs layout with the given layout data.
 * @method static Modal     modal(string $key, string[]|string|BaseLayout $layouts) Creates a new modal layout with the given key and layout data.
 * @method static Blank     blank(BaseLayout[] $layouts)                            Creates a new blank layout with the given layout data.
 * @method static Wrapper   wrapper(string $template, array $layouts)               Creates a new wrapper layout with the given template and layout data.
 * @method static Accordion accordion(BaseLayout[] $layouts)                        Creates a new accordion layout with the given layout data.
 * @method static Selection selection(array $filters)                               Creates a new selection layout with the given filters.
 * @method static Block     block(BaseLayout|string|string[] $layouts)              Creates a new block layout with the given layout data.
 * @method static Legend    legend(string $target, array $columns)                  Creates a new legend layout with the given target and columns.
 * @method static Browsing  browsing(string $src)                                   Creates a new browsing layout with the given src.
 * @method static Metric    metrics(array $labels)                                  Creates a new metrics layout with the given labels.
 * @method static Split     split(array $layouts)                                   Creates a new split layout with the given layout data.
 * @method static Chart     chart(string $target, string $title = null)             Creates a new chart layout with the given title.
 * @method static Sortable  sortable(string $target, array $columns)                Creates a new sortable layout with the given target and columns.
 */
class Layout extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LayoutFactory::class;
    }
}
