<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

/**
 * A single configuration item (one rendered field on a group-edit screen).
 */
class ConfigItem
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        private readonly string $key,
        private readonly string $type,
        private readonly mixed $default = null,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private array $attributes = [],
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getTitle(): string
    {
        return $this->title ? __($this->title) : $this->key;
    }

    public function getDescription(): ?string
    {
        return $this->description ? __($this->description) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
