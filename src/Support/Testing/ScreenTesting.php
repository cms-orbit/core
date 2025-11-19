<?php

namespace CmsOrbit\Core\Testing;

trait ScreenTesting
{
    /**
     * Get a DynamicTestScreen object.
     *
     * @param string|null $name       Name of the screen
     * @param array       $parameters
     *
     * @return \CmsOrbit\Core\Support\Testing\DynamicTestScreen
     */
    public function screen(?string $name = null, array $parameters = []): DynamicTestScreen
    {
        return (new DynamicTestScreen($name))
            ->parameters($parameters);
    }
}
