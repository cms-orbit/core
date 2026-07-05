<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Layouts;

use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Layouts\Rows;
use CmsOrbit\Core\Screen\Repository;

/**
 * Rows layout that binds entity fields under the shared "model" prefix, both
 * for the legacy Blade build() path and the React serialize() path.
 */
class ResourceFields extends Rows
{
    public const PREFIX = 'model';

    /**
     * @param Field[] $fields
     */
    public function __construct(private array $fieldset) {}

    protected function fields(): array
    {
        return $this->fieldset;
    }

    public function build(Repository $repository)
    {
        $form = new Builder($this->fields(), $repository);

        return view($this->template, [
            'form'  => $form->setPrefix(self::PREFIX)->generateForm(),
            'title' => $this->title,
        ]);
    }

    protected function serialize(Repository $repository): array
    {
        return [
            'title'  => $this->title,
            'fields' => (new Builder($this->fields(), $repository))
                ->setPrefix(self::PREFIX)
                ->generateArray(),
            'wrapped' => $this->wrapped,
        ];
    }

    /**
     * Render fields inline (no Card wrapper). Use inside a parent Block/column.
     */
    public function unwrapped(): self
    {
        $this->wrapped = false;

        return $this;
    }

    protected bool $wrapped = true;
}
