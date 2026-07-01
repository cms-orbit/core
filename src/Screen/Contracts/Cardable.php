<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Contracts;

use CmsOrbit\Core\Support\Color;

interface Cardable
{
    public function title(): string;

    public function description(): string;

    public function image(): ?string;

    public function color(): ?Color;
}
