<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config\Layouts;

use CmsOrbit\Core\Config\ConfigFieldFactory;
use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Layouts\Rows;
use CmsOrbit\Core\Screen\Repository;

/**
 * Rows layout binding config fields under the "config" prefix.
 */
class ConfigFields extends Rows
{
    /**
     * @param  Field[]  $fieldset
     */
    public function __construct(private array $fieldset, ?string $title = null)
    {
        $this->title = $title;
    }

    protected function fields(): array
    {
        return $this->fieldset;
    }

    public function build(Repository $repository)
    {
        $form = new Builder($this->fields(), $repository);

        return view($this->template, [
            'form' => $form->setPrefix(ConfigFieldFactory::PREFIX)->generateForm(),
            'title' => $this->title,
        ]);
    }

    protected function serialize(Repository $repository): array
    {
        return [
            'title' => $this->title,
            'fields' => (new Builder($this->fields(), $repository))
                ->setPrefix(ConfigFieldFactory::PREFIX)
                ->generateArray(),
        ];
    }
}
