<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Layouts;

use Illuminate\View\View;
use CmsOrbit\Core\UI\Contracts\Personable;

class Persona extends Content
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.persona';

    public function render(Personable $user): View
    {
        return view($this->template, [
            'title'    => $user->title(),
            'subTitle' => $user->subTitle(),
            'image'    => $user->image(),
            'url'      => $user->url(),
        ]);
    }
}
