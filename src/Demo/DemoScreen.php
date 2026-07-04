<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo;

use CmsOrbit\Core\Screen\Screen;

/**
 * Base screen for non-production demo pages (permission: orbit.index).
 */
abstract class DemoScreen extends Screen
{
    public function permission(): ?iterable
    {
        return ['orbit.index'];
    }
}
