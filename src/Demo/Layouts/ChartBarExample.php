<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Layouts;

use CmsOrbit\Core\Screen\Layouts\Chart;

class ChartBarExample extends Chart
{
    protected $type = self::TYPE_BAR;

    protected $height = 300;
}
