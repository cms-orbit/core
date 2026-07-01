<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

/**
 * A logical grouping of config items inside a group (rendered as a fieldset).
 */
class ConfigSection
{
    /**
     * @var array<string, ConfigItem>
     */
    private array $items = [];

    public function __construct(
        private readonly string $name,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private int $priority = 0,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title ? __($this->title) : null;
    }

    public function getDescription(): ?string
    {
        return $this->description ? __($this->description) : null;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function addItem(ConfigItem $item): void
    {
        $this->items[$item->getKey()] = $item;
    }

    public function hasItem(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function getItem(string $key): ?ConfigItem
    {
        return $this->items[$key] ?? null;
    }

    /**
     * @return array<string, ConfigItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
