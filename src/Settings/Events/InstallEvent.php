<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Settings\Events;

use Illuminate\Console\Command;

/**
 * Class InstallEvent.
 */
class InstallEvent
{
    /**
     * InstallEvent constructor.
     */
    public function __construct(public Command $command) {}
}
