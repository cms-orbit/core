<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

use CmsOrbit\Core\Config\Models\OrbitConfig;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Registry of configuration metadata (groups/sections/items) plus a value store
 * with an XE3-style chain-of-responsibility fallback.
 *
 * Resolution order for get($key, $default, $instanceId):
 *   1. stored (key, instanceId)            – instance override
 *   2. stored (key, null)                  – global value
 *   3. stored dot-notation parent chain    – type.{id} → type
 *   4. registered item default
 *   5. caller-supplied default
 */
class ConfigRegistry
{
    /**
     * @var array<string, ConfigGroup>
     */
    protected array $groups = [];

    public function registerGroup(string $name, int $priority = 0, array $attributes = []): ConfigGroup
    {
        if (! isset($this->groups[$name])) {
            $this->groups[$name] = new ConfigGroup(
                $name,
                $priority,
                $attributes['icon'] ?? null,
                $attributes['title'] ?? null,
                $attributes['description'] ?? null,
                $attributes['hubSection'] ?? null,
            );
            $this->sortGroups();
        }

        return $this->groups[$name];
    }

    public function registerSection(string $groupName, string $sectionName, array $attributes = []): ConfigSection
    {
        $group = $this->group($groupName);

        return $group->addSection(
            $sectionName,
            $attributes['title'] ?? null,
            $attributes['description'] ?? null,
            $attributes['priority'] ?? 0,
        );
    }

    public function registerItem(
        string $groupName,
        string $key,
        string $type,
        mixed $default = null,
        string $sectionName = 'default',
        array $attributes = [],
    ): ConfigItem {
        $group = $this->group($groupName);

        $item = new ConfigItem(
            $key,
            $type,
            $default,
            $attributes['title'] ?? null,
            $attributes['description'] ?? null,
            $attributes,
        );

        $group->addItem($item, $sectionName);

        return $item;
    }

    /**
     * @return array<string, ConfigGroup>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroup(string $name): ?ConfigGroup
    {
        return $this->groups[$name] ?? null;
    }

    public function getGroupByUriKey(string $uriKey): ?ConfigGroup
    {
        foreach ($this->groups as $group) {
            if ($group->getUriKey() === $uriKey) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Find a registered item by its key across all groups.
     */
    public function findItem(string $key): ?ConfigItem
    {
        foreach ($this->groups as $group) {
            if ($item = $group->getItem($key)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Resolve a configuration value with CoR fallback.
     */
    public function get(string $key, mixed $default = null, ?int $instanceId = null): mixed
    {
        $item = $this->findItem($key);

        foreach ($this->candidates($key, $instanceId) as [$candidateKey, $candidateInstance]) {
            $row = OrbitConfig::query()
                ->where('key', $candidateKey)
                ->where('instance_id', $candidateInstance)
                ->first();

            if ($row !== null) {
                $payload = $row->value ?? [];
                $value = $payload['data'] ?? null;
                $encrypted = (bool) ($payload['encrypted'] ?? false)
                    || (bool) $item?->getAttribute('encrypted', false)
                    || $item?->getType() === 'secret';

                if (! $encrypted || ! is_string($value) || $value === '') {
                    return $value;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            }
        }

        return $item?->getDefault() ?? $default;
    }

    /**
     * Persist a configuration value.
     */
    public function set(string $key, mixed $value, ?int $instanceId = null): void
    {
        $item = $this->findItem($key);
        $encrypted = (bool) $item?->getAttribute('encrypted', false) || $item?->getType() === 'secret';

        if ($encrypted && is_string($value) && $value !== '') {
            $value = Crypt::encryptString($value);
        }

        OrbitConfig::query()->updateOrCreate(
            ['key' => $key, 'instance_id' => $instanceId],
            ['value' => ['data' => $value, 'encrypted' => $encrypted]],
        );
    }

    /**
     * Build the ordered list of (key, instanceId) lookup candidates.
     *
     * @return array<int, array{0: string, 1: int|null}>
     */
    protected function candidates(string $key, ?int $instanceId): array
    {
        $candidates = [];

        if ($instanceId !== null) {
            $candidates[] = [$key, $instanceId];
        }

        $candidates[] = [$key, null];

        $parent = $key;
        while (Str::contains($parent, '.')) {
            $parent = Str::beforeLast($parent, '.');

            if ($instanceId !== null) {
                $candidates[] = [$parent, $instanceId];
            }

            $candidates[] = [$parent, null];
        }

        return $candidates;
    }

    protected function group(string $name): ConfigGroup
    {
        return $this->groups[$name] ?? throw new \InvalidArgumentException("Config group '{$name}' is not registered.");
    }

    protected function sortGroups(): void
    {
        uasort($this->groups, fn (ConfigGroup $a, ConfigGroup $b) => $b->getPriority() <=> $a->getPriority());
    }
}
