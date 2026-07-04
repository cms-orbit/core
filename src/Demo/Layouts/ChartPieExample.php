<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Layouts;

use CmsOrbit\Core\Screen\Layouts\Chart;

class ChartPieExample extends Chart
{
    protected $type = self::TYPE_PIE;

    protected $height = 300;
}
