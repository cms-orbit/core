<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

use Illuminate\Support\Str;

/**
 * A configuration group surfaced as a card in the Settings hub and an editable
 * screen of its own.
 */
class ConfigGroup
{
    /**
     * @var array<string, ConfigSection>
     */
    private array $sections = [];

    private string $uriKey;

    public function __construct(
        private readonly string $name,
        private int $priority = 0,
        private readonly ?string $icon = null,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
    ) {
        $this->uriKey = Str::slug($name);
        $this->addSection('default');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title ? __($this->title) : __($this->name);
    }

    public function getDescription(): ?string
    {
        return $this->description ? __($this->description) : null;
    }

    public function getIcon(): string
    {
        return $this->icon ?? 'bs.gear';
    }

    public function getUriKey(): string
    {
        return $this->uriKey;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Permission slugs that grant access to this config group.
     *
     * @return string[]
     */
    public function accessiblePermissions(): array
    {
        $permissions = ['orbit.configs', $this->getPermission()];

        if ($this->uriKey === 'admin-design') {
            $permissions[] = 'orbit.configs.appearance';
            $permissions[] = 'orbit.configs.branding';
            $permissions[] = 'orbit.configs.theme';
        }

        return $permissions;
    }

    /**
     * Whether the given user may view and edit this config group.
     */
    public function isAccessibleBy(?object $user): bool
    {
        if ($user === null || ! method_exists($user, 'hasAnyAccess')) {
            return true;
        }

        return $user->hasAnyAccess($this->accessiblePermissions());
    }

    /**
     * The per-group permission slug submitted to Core.
     */
    public function getPermission(): string
    {
        return 'orbit.configs.'.$this->uriKey;
    }

    public function addSection(string $name, ?string $title = null, ?string $description = null, int $priority = 0): ConfigSection
    {
        if (! isset($this->sections[$name])) {
            $this->sections[$name] = new ConfigSection($name, $title, $description, $priority);
        }

        return $this->sections[$name];
    }

    public function getSection(string $name): ?ConfigSection
    {
        return $this->sections[$name] ?? null;
    }

    /**
     * @return array<string, ConfigSection>
     */
    public function getSections(): array
    {
        return collect($this->sections)
            ->sortByDesc(fn (ConfigSection $section) => $section->getPriority())
            ->all();
    }

    public function addItem(ConfigItem $item, string $sectionName = 'default'): void
    {
        $this->addSection($sectionName)->addItem($item);
    }

    public function getItem(string $key): ?ConfigItem
    {
        foreach ($this->sections as $section) {
            if ($section->hasItem($key)) {
                return $section->getItem($key);
            }
        }

        return null;
    }

    public function hasItem(string $key): bool
    {
        return $this->getItem($key) !== null;
    }

    /**
     * @return array<string, ConfigItem>
     */
    public function getItems(): array
    {
        $items = [];

        foreach ($this->sections as $section) {
            $items += $section->getItems();
        }

        return $items;
    }
}
