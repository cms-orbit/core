<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud;

use CmsOrbit\Core\Screen\Actions\Button;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * A bulk action that can be applied to one or more selected models on the list
 * screen.
 */
abstract class Action
{
    /**
     * The button that triggers the action.
     */
    abstract public function button(): Button;

    /**
     * Perform the action on the given models.
     *
     * @param  Collection<int, Model>  $models
     * @return mixed
     */
    abstract public function handle(Collection $models);

    /**
     * Unique action identifier used in the request payload.
     */
    public static function name(): string
    {
        return (string) Str::of(static::class)->replace('\\', '-')->slug();
    }
}
