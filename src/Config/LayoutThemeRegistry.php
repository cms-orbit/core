<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

use CmsOrbit\Core\Support\Facades\Config;

/**
 * Registry of admin shell layout modes and their per-layout colour themes.
 * Core registers built-in modes; host apps may register additional layouts.
 */
class LayoutThemeRegistry
{
    /**
     * @var array<string, string>
     */
    private array $modes = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $themes = [];

    /**
     * @param array<string, string>               $modes
     * @param array<string, array<string, mixed>> $themes
     */
    public function registerDefaults(array $modes, array $themes): void
    {
        foreach ($modes as $mode => $label) {
            $this->modes[$mode] = $label;
        }

        foreach ($themes as $mode => $definition) {
            $this->themes[$mode] = $definition;
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function registerLayout(string $mode, string $label, array $definition): void
    {
        $this->modes[$mode] = $label;
        $this->themes[$mode] = $definition;
    }

    /**
     * @return array<string, string>
     */
    public function getModes(): array
    {
        return $this->modes;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getThemes(): array
    {
        return $this->themes;
    }

    /**
     * @return string[]
     */
    public function themeFieldKeys(): array
    {
        $keys = [];

        foreach ($this->themes as $mode => $definition) {
            $keys[] = "theme.{$mode}.palette";

            $tokens = $definition['tokens'] ?? [];

            if ($definition['dualTone'] ?? false) {
                foreach (['light', 'dark'] as $tone) {
                    foreach ($tokens as $token) {
                        $keys[] = "theme.{$mode}.{$tone}.{$token['key']}";
                    }
                }

                continue;
            }

            foreach ($tokens as $token) {
                $keys[] = "theme.{$mode}.{$token['key']}";
            }
        }

        return $keys;
    }

    /**
     * Persist config items for every registered layout theme under Admin Design.
     */
    public function registerConfigItems(): void
    {
        $priority = 19;

        foreach ($this->themes as $mode => $definition) {
            $this->registerLayoutConfig($mode, $priority--);
        }
    }

    /**
     * Persist config items for a single layout theme (for host extensions).
     */
    public function registerLayoutConfig(string $mode, ?int $priority = null): void
    {
        $definition = $this->themes[$mode] ?? null;

        if ($definition === null) {
            return;
        }

        $section = 'theme-'.$mode;
        $label = $this->modes[$mode] ?? $mode;
        $sectionPriority = $priority ?? 5;

        Config::registerSection('Admin Design', $section, ['title' => __($label), 'priority' => $sectionPriority]);

        $presets = $definition['presets'] ?? [];
        $presetOptions = collect($presets)
            ->mapWithKeys(fn (array $preset, string $key) => [$key => $preset['label']])
            ->put('custom', 'Custom')
            ->all();

        $defaultPreset = array_key_first($presets);

        Config::registerItem('Admin Design', "theme.{$mode}.palette", 'select', $defaultPreset, $section, [
            'title'   => 'Colour preset',
            'options' => collect($presetOptions)
                ->mapWithKeys(fn (string $label, string $key) => [$key => __($label)])
                ->all(),
        ]);

        $tokens = $definition['tokens'] ?? [];
        $dualTone = $definition['dualTone'] ?? false;

        if ($dualTone) {
            foreach (['light', 'dark'] as $tone) {
                foreach ($tokens as $token) {
                    $default = $presets[$defaultPreset][$tone][$token['key']]
                        ?? $presets[$defaultPreset]['colors'][$token['key']]
                        ?? $this->fallbackTokenDefault($token['key'], $tone);

                    Config::registerItem(
                        'Admin Design',
                        "theme.{$mode}.{$tone}.{$token['key']}",
                        'color',
                        $default,
                        $section,
                        ['title' => __(ucfirst($tone)).' '.__($token['label'])],
                    );
                }
            }

            return;
        }

        foreach ($tokens as $token) {
            $default = $presets[$defaultPreset]['colors'][$token['key']] ?? $this->fallbackTokenDefault($token['key'], 'light');

            Config::registerItem('Admin Design', "theme.{$mode}.{$token['key']}", 'color', $default, $section, [
                'title' => __($token['label']),
            ]);
        }
    }

    /**
     * Refresh the active layout select options after host layouts are registered.
     */
    public function syncLayoutModeOptions(): void
    {
        Config::registerItem('Admin Design', 'layout.mode', 'select', array_key_first($this->modes) ?? 'palette-split', 'layout', [
            'title'   => 'Active layout',
            'options' => collect($this->modes)
                ->mapWithKeys(fn (string $label, string $key) => [$key => __($label)])
                ->all(),
        ]);
    }

    /**
     * Resolve stored colour tokens for a layout mode and colour tone.
     *
     * @return array<string, string>
     */
    public function resolveColors(string $mode, string $tone = 'light'): array
    {
        $definition = $this->themes[$mode] ?? null;

        if ($definition === null) {
            return [];
        }

        $tokens = $definition['tokens'] ?? [];
        $colors = [];

        foreach ($tokens as $token) {
            $key = $token['key'];

            if ($definition['dualTone'] ?? false) {
                $colors[$key] = (string) orbit_config("theme.{$mode}.{$tone}.{$key}", '#64748b');

                continue;
            }

            $colors[$key] = (string) orbit_config("theme.{$mode}.{$key}", '#64748b');
        }

        return $colors;
    }

    protected function fallbackTokenDefault(string $key, string $tone): string
    {
        return match ($key) {
            'color_nav_section_fg'  => $tone === 'dark' ? '#e2e8f0' : '#334155',
            'color_nav_group_fg'    => $tone === 'dark' ? '#cbd5e1' : '#475569',
            'color_rail_symbol_bg'  => $tone === 'dark' ? '#065f46' : '#17ce91',
            default                 => '#64748b',
        };
    }
}
