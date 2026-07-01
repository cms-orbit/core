<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Contracts;

interface Fieldable
{
    /**
     * The process of creating.
     *
     * @return mixed
     */
    public function render();

    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function get(string $key, $value = null);

    /**
     * @param  mixed  $value
     * @return $this
     */
    public function set(string $key, $value);

    public function getAttributes(): array;

    /**
     * Serialize the field to the React JSON contract (FieldNode).
     *
     * @return array<string, mixed>|null
     */
    public function toArray(): ?array;

    /**
     * The React component key used to render this field.
     */
    public function getComponent(): string;
}
