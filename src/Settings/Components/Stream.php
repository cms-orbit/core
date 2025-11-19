<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Stream extends Component
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('settings::components.stream');
    }
}
