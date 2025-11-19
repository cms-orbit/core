<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use ArrayAccess;
use Illuminate\View\View;
use CmsOrbit\Core\Screen\Contracts\Personable;

class Facepile extends Content
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.facepile';

    /**
     * @param Personable[] $users
     */
    public function render(ArrayAccess $users): View
    {
        return view($this->template, [
            'users' => $users,
        ]);
    }
}
