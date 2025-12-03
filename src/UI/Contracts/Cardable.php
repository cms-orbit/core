<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Contracts;

use CmsOrbit\Core\Support\Color;

interface Cardable
{
    public function title(): string;

    public function description(): string;

    /**
     * @return string
     */
    public function image(): ?string;

    /**
     * @return Color
     */
    public function color(): ?Color;
}
