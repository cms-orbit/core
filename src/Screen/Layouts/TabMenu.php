<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Layouts\Concerns\SerializesMenu;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Throwable;

/**
 * Class TabMenu.
 */
abstract class TabMenu extends Layout
{
    use SerializesMenu;

    /**
     * @var string
     */
    protected $template = 'orbit::layouts.tabMenu';

    /**
     * Serialize the navigation items to the React contract.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        return ['items' => $this->serializeNavigations($this->navigations(), $repository)];
    }

    /**
     * @return Factory|View|void
     *
     * @throws Throwable
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
            'navigations' => $form->generateForm(),
        ]);
    }

    /**
     * Get the menu elements to be displayed.
     *
     * @return Menu[]
     */
    abstract protected function navigations(): iterable;
}
