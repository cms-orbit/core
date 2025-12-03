<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use Illuminate\Contracts\View\Factory;
use CmsOrbit\Core\UI\Actions\Menu;
use CmsOrbit\Core\UI\Builder;
use CmsOrbit\Core\UI\Layout;
use CmsOrbit\Core\UI\Repository;
use Throwable;

/**
 * Class TabMenu.
 */
abstract class TabMenu extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.tabMenu';

    /**
     * @throws Throwable
     *
     * @return Factory|\Illuminate\View\View|void
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        $navigations = $this->navigations();

        if (! $this->isSee() || empty($navigations)) {
            return;
        }

        $form = new Builder($navigations, $repository);

        return view($this->template, [
            'navigations'  => $form->generateForm(),
        ]);
    }

    /**
     * Get the menu elements to be displayed.
     *
     * @return Menu[]
     */
    abstract protected function navigations(): iterable;
}
