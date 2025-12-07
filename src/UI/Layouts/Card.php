<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Layouts;

use Illuminate\View\View;
use CmsOrbit\Core\UI\Action;
use CmsOrbit\Core\UI\Contracts\Actionable;
use CmsOrbit\Core\UI\Contracts\Cardable;

class Card extends Content
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.card';

    /**
     * @var array|Action[]
     */
    protected $commandBar;

    /**
     * Card constructor.
     *
     * @param string|Cardable $target
     * @param Action[]        $commandBar
     */
    public function __construct($target, array $commandBar = [])
    {
        parent::__construct($target);

        $this->commandBar = $commandBar;
    }

    public function render(Cardable $card): View
    {
        return view($this->template, [
            'title'       => $card->title(),
            'description' => $card->description(),
            'image'       => $card->image(),
            'commandBar'  => $this->buildCommandBar(),
            'color'       => $card->color()?->name(),
        ]);
    }

    private function buildCommandBar(): array
    {
        return collect($this->commandBar)
            ->map(fn (Actionable $command) => $command->build($this->query))->all();
    }
}
