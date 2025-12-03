<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Concerns;

use Closure;

trait CanSee
{
    /**
     * Callback to determine if the element should be displayed.
     *
     * @var Closure|null
     */
    protected $seeCallback;

    /**
     * Set a callback to determine if the element should be displayed.
     *
     * @param Closure|bool $callback
     * @return $this
     */
    public function canSee($callback): static
    {
        $this->seeCallback = $callback;

        return $this;
    }

    /**
     * Determine if the element should be displayed.
     *
     * @return bool
     */
    public function isSee(): bool
    {
        if ($this->seeCallback instanceof Closure) {
            return (bool) call_user_func($this->seeCallback);
        }

        if (is_bool($this->seeCallback)) {
            return $this->seeCallback;
        }

        return true;
    }

    /**
     * Hide the element.
     *
     * @return $this
     */
    public function hide(): static
    {
        return $this->canSee(false);
    }
}

