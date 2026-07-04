<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Layouts;

use CmsOrbit\Core\Screen\Layouts\Chart;

class ChartLineExample extends Chart
{
    protected $type = self::TYPE_LINE;

    protected $height = 300;
}
