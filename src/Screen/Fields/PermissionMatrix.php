<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Fields;

use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Support\Collection;

/**
 * Grouped permission picker: renders the registered permissions as checkboxes
 * grouped by their submitting entity/section, each group carrying a "select
 * all" toggle. Restores the Orbit 3.1 UX (previously a flat multi-select).
 *
 * The field can also receive inherited role permissions, allowing the user
 * entity to expose `inherit / allow / deny` overrides while still reusing the
 * same grouped permission UI.
 *
 * @method static PermissionMatrix make(string $name)
 */
class PermissionMatrix extends Field
{
    /**
     * @var string
     */
    protected $component = 'permission-matrix';

    /**
     * @var array
     */
    protected $attributes = [
        'value' => null,
    ];

    /**
     * Grouped permission source: group name => [{slug, description}]. Defaults
     * to the full registered permission set.
     */
    private ?Collection $groups = null;

    /**
     * Override the grouped permission source.
     */
    public function permissions(Collection $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Permissions already inherited through another source (for example roles).
     *
     * @param iterable<string, mixed> $permissions
     */
    public function inheritedPermissions(iterable $permissions): static
    {
        return $this->set('inheritedPermissions', collect($permissions)
            ->mapWithKeys(fn ($enabled, $slug) => [(string) $slug => (bool) $enabled])
            ->all());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toArray(): ?array
    {
        $node = parent::toArray();

        if ($node === null) {
            return null;
        }

        $node['props'] = [
            'groups'               => $this->buildGroups(),
            'inheritedPermissions' => $this->get('inheritedPermissions', []),
        ];

        return $node;
    }

    /**
     * Normalise the grouped permissions for the React component, translating the
     * group name and each permission description.
     *
     * @return array<int, array{group: string, permissions: array<int, array{slug: string, label: string}>}>
     */
    private function buildGroups(): array
    {
        $groups = $this->groups ?? Orbit::getPermission();

        return collect($groups)
            ->map(fn ($items, $group) => [
                'group'       => __((string) $group),
                'permissions' => collect($items)
                    ->map(fn (array $item) => [
                        'slug'  => (string) $item['slug'],
                        'label' => __((string) ($item['description'] ?? $item['slug'])),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group) => $group['permissions'] !== [])
            ->values()
            ->all();
    }
}
