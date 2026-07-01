<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Concerns;

use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Contracts\Actionable;
use CmsOrbit\Core\Screen\Repository;

trait HasCommandBar
{
    /**
     * @return Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    protected function buildCommandBar(Repository $repository): array
    {
        return collect($this->commandBar())
            ->map(static fn (Actionable $command) => $command->build($repository))
            ->filter()
            ->all();
    }

    /**
     * Serialize the command bar to the React JSON contract (FieldNode[]).
     *
     * @return array<int, mixed>
     */
    protected function buildCommandBarArray(Repository $repository): array
    {
        return collect($this->commandBar())
            ->map(static fn (Action $command) => $command->toArray())
            ->filter()
            ->values()
            ->all();
    }
}
