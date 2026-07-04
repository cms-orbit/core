<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Layouts;

use CmsOrbit\Core\Screen\Layouts\Chart;

class ChartPercentageExample extends Chart
{
    protected $type = self::TYPE_PERCENTAGE;

    protected $height = 300;
}
