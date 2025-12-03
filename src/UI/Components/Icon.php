<?php

namespace CmsOrbit\Core\UI\Components;

use CmsOrbit\Core\Foundation\Icons\IconFinder;
use Illuminate\View\Component;

class Icon extends Component
{
    public function __construct(
        public ?string $path = null,
        public string $class = '',
        public string $width = '1em',
        public string $height = '1em'
    ) {
    }

    public function render(): string
    {
        if (!$this->path) {
            return '';
        }

        $finder = app(IconFinder::class);
        
        if ($this->width !== '1em' || $this->height !== '1em') {
            $finder->setSize($this->width, $this->height);
        }
        
        $icon = $finder->loadFile($this->path);
        
        if (!$icon) {
            return '';
        }
        
        // Add class if provided
        if ($this->class) {
            $icon = new \CmsOrbit\Core\Foundation\Icons\Icon($icon);
            return $icon->setAttributes(['class' => $this->class]);
        }
        
        return $icon;
    }
}

