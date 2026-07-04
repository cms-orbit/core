<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Fields;

use CmsOrbit\Core\Screen\Field;

/**
 * Class ViewField.
 *
 * @method $this name(string $value = null)
 * @method $this help(string $value = null)
 */
class ViewField extends Field
{
    public function view(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toArray(): ?array
    {
        $node = parent::toArray();

        if ($node === null || $this->view === null) {
            return $node;
        }

        $node['attributes']['html'] = view($this->view, $this->getAttributes())->render();

        return $node;
    }
}
