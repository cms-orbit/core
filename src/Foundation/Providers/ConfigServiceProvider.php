<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Config\LayoutThemeRegistry;
use CmsOrbit\Core\Foundation\ItemPermission;
use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Support\Facades\Config;
use CmsOrbit\Core\Support\Facades\Orbit;
use CmsOrbit\Core\Support\Locale;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Config registry, Core default config groups, and submits the
 * per-group permissions + Settings menu to Core.
 */
class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Available admin layout modes (value => label). The colour settings below
     * are maintained per mode while branding (name/logo/favicon) stays common.
     *
     * @var array<string, string>
     */
    public const LAYOUT_MODES = [
        'palette-split'  => 'Palette split sidebar',
        'sidebar-single' => 'Single sidebar',
        'topbar'         => 'Top bar',
        'hybrid'         => 'Hybrid',
    ];

    /**
     * Built-in layout theme metadata. Host apps may extend this via
     * LayoutThemeRegistry without modifying core.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function builtInLayoutThemes(): array
    {
        return [
            'palette-split' => [
                'dualTone' => true,
                'tokens'   => self::paletteSplitTokens(),
                'presets'  => self::paletteSplitPresets(),
            ],
            'sidebar-single' => [
                'dualTone' => true,
                'tokens'   => self::singleSidebarTokens(),
                'presets'  => self::singleSidebarPresets(),
            ],
            'topbar' => [
                'dualTone' => true,
                'tokens'   => self::topbarTokens(),
                'presets'  => self::topbarPresets(),
            ],
            'hybrid' => [
                'dualTone' => true,
                'tokens'   => self::hybridTokens(),
                'presets'  => self::hybridPresets(),
            ],
        ];
    }

    /**
     * Config keys for per-layout theme tokens (palette + colour fields).
     *
     * @return string[]
     */
    public static function designSettingThemeFieldKeys(): array
    {
        return app(LayoutThemeRegistry::class)->themeFieldKeys();
    }

    /**
     * All config keys rendered by the admin design settings screen.
     *
     * @return string[]
     */
    public static function designSettingFieldKeys(): array
    {
        return [
            'branding.name',
            'branding.logo',
            'branding.symbol',
            'branding.favicon',
            'branding.dark_mode',
            'layout.mode',
            ...self::designSettingThemeFieldKeys(),
        ];
    }

    /**
     * @return array{key: string, label: string, group: string}
     */
    protected static function token(string $key, string $label, string $group): array
    {
        return ['key' => $key, 'label' => $label, 'group' => $group];
    }

    /**
     * @return array<int, array{key: string, label: string, group: string}>
     */
    protected static function commonShellTokens(): array
    {
        return [
            self::token('color_primary', 'Brand', 'Brand'),
            self::token('color_secondary', 'Text', 'Brand'),
            self::token('color_accent', 'Accent', 'Brand'),
            self::token('color_page_bg', 'Page background', 'Content'),
            self::token('color_panel_bg', 'Panel background', 'Content'),
            self::token('color_panel_border', 'Panel border', 'Content'),
            self::token('color_header_bg', 'Header background', 'Header'),
            self::token('color_header_border', 'Header border', 'Header'),
            self::token('color_nav_bg', 'Navigation background', 'Navigation'),
            self::token('color_nav_border', 'Navigation border', 'Navigation'),
            self::token('color_nav_muted', 'Navigation hover / muted', 'Navigation'),
            self::token('color_nav_active_bg', 'Navigation active background', 'Navigation'),
            self::token('color_nav_active_fg', 'Navigation active text', 'Navigation'),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, group: string}>
     */
    protected static function paletteSplitTokens(): array
    {
        return [
            ...self::commonShellTokens(),
            self::token('color_rail_bg', 'Rail background', 'Rail'),
            self::token('color_rail_border', 'Rail border', 'Rail'),
            self::token('color_rail_icon', 'Rail icon', 'Rail'),
            self::token('color_rail_active_bg', 'Rail active background', 'Rail'),
            self::token('color_rail_active_fg', 'Rail active text', 'Rail'),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, group: string}>
     */
    protected static function singleSidebarTokens(): array
    {
        return self::commonShellTokens();
    }

    /**
     * @return array<int, array{key: string, label: string, group: string}>
     */
    protected static function topbarTokens(): array
    {
        return self::commonShellTokens();
    }

    /**
     * @return array<int, array{key: string, label: string, group: string}>
     */
    protected static function hybridTokens(): array
    {
        return self::commonShellTokens();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function paletteSplitPresets(): array
    {
        return [
            'orbit' => [
                'label' => 'Orbit',
                'light' => [
                    'color_primary'        => '#17ce91',
                    'color_secondary'      => '#0f172a',
                    'color_accent'         => '#fc8024',
                    'color_page_bg'        => '#f8fafc',
                    'color_panel_bg'       => '#ffffff',
                    'color_panel_border'   => '#e2e8f0',
                    'color_header_bg'      => '#ffffff',
                    'color_header_border'  => '#e2e8f0',
                    'color_nav_bg'         => '#ffffff',
                    'color_nav_border'     => '#e2e8f0',
                    'color_nav_muted'      => '#f1f5f9',
                    'color_nav_active_bg'  => '#ecfdf5',
                    'color_nav_active_fg'  => '#0f766e',
                    'color_rail_bg'        => '#ffffff',
                    'color_rail_border'    => '#e2e8f0',
                    'color_rail_icon'      => '#64748b',
                    'color_rail_active_bg' => '#17ce91',
                    'color_rail_active_fg' => '#ffffff',
                ],
                'dark' => [
                    'color_primary'        => '#2dd4a8',
                    'color_secondary'      => '#e2e8f0',
                    'color_accent'         => '#fb923c',
                    'color_page_bg'        => '#020617',
                    'color_panel_bg'       => '#0f172a',
                    'color_panel_border'   => '#1e293b',
                    'color_header_bg'      => '#0f172a',
                    'color_header_border'  => '#1e293b',
                    'color_nav_bg'         => '#0f172a',
                    'color_nav_border'     => '#1e293b',
                    'color_nav_muted'      => '#111827',
                    'color_nav_active_bg'  => '#083344',
                    'color_nav_active_fg'  => '#99f6e4',
                    'color_rail_bg'        => '#020617',
                    'color_rail_border'    => '#1e293b',
                    'color_rail_icon'      => '#94a3b8',
                    'color_rail_active_bg' => '#0f766e',
                    'color_rail_active_fg' => '#ffffff',
                ],
            ],
            'ocean' => [
                'label' => 'Ocean',
                'light' => [
                    'color_primary'        => '#0284c7',
                    'color_secondary'      => '#0f172a',
                    'color_accent'         => '#06b6d4',
                    'color_page_bg'        => '#f0f9ff',
                    'color_panel_bg'       => '#ffffff',
                    'color_panel_border'   => '#dbeafe',
                    'color_header_bg'      => '#ffffff',
                    'color_header_border'  => '#dbeafe',
                    'color_nav_bg'         => '#ffffff',
                    'color_nav_border'     => '#dbeafe',
                    'color_nav_muted'      => '#e0f2fe',
                    'color_nav_active_bg'  => '#e0f2fe',
                    'color_nav_active_fg'  => '#075985',
                    'color_rail_bg'        => '#ecfeff',
                    'color_rail_border'    => '#bae6fd',
                    'color_rail_icon'      => '#475569',
                    'color_rail_active_bg' => '#0284c7',
                    'color_rail_active_fg' => '#ffffff',
                ],
                'dark' => [
                    'color_primary'        => '#38bdf8',
                    'color_secondary'      => '#e0f2fe',
                    'color_accent'         => '#22d3ee',
                    'color_page_bg'        => '#082f49',
                    'color_panel_bg'       => '#0c4a6e',
                    'color_panel_border'   => '#155e75',
                    'color_header_bg'      => '#0c4a6e',
                    'color_header_border'  => '#155e75',
                    'color_nav_bg'         => '#0c4a6e',
                    'color_nav_border'     => '#155e75',
                    'color_nav_muted'      => '#164e63',
                    'color_nav_active_bg'  => '#0369a1',
                    'color_nav_active_fg'  => '#ffffff',
                    'color_rail_bg'        => '#082f49',
                    'color_rail_border'    => '#155e75',
                    'color_rail_icon'      => '#bae6fd',
                    'color_rail_active_bg' => '#0284c7',
                    'color_rail_active_fg' => '#ffffff',
                ],
            ],
            'forest' => [
                'label' => 'Forest',
                'light' => [
                    'color_primary'        => '#059669',
                    'color_secondary'      => '#1f2937',
                    'color_accent'         => '#f59e0b',
                    'color_page_bg'        => '#f0fdf4',
                    'color_panel_bg'       => '#ffffff',
                    'color_panel_border'   => '#dcfce7',
                    'color_header_bg'      => '#ffffff',
                    'color_header_border'  => '#dcfce7',
                    'color_nav_bg'         => '#ffffff',
                    'color_nav_border'     => '#dcfce7',
                    'color_nav_muted'      => '#ecfdf5',
                    'color_nav_active_bg'  => '#d1fae5',
                    'color_nav_active_fg'  => '#065f46',
                    'color_rail_bg'        => '#ecfdf5',
                    'color_rail_border'    => '#bbf7d0',
                    'color_rail_icon'      => '#4b5563',
                    'color_rail_active_bg' => '#059669',
                    'color_rail_active_fg' => '#ffffff',
                ],
                'dark' => [
                    'color_primary'        => '#34d399',
                    'color_secondary'      => '#ecfdf5',
                    'color_accent'         => '#fbbf24',
                    'color_page_bg'        => '#052e16',
                    'color_panel_bg'       => '#14532d',
                    'color_panel_border'   => '#166534',
                    'color_header_bg'      => '#14532d',
                    'color_header_border'  => '#166534',
                    'color_nav_bg'         => '#14532d',
                    'color_nav_border'     => '#166534',
                    'color_nav_muted'      => '#166534',
                    'color_nav_active_bg'  => '#047857',
                    'color_nav_active_fg'  => '#ecfdf5',
                    'color_rail_bg'        => '#052e16',
                    'color_rail_border'    => '#166534',
                    'color_rail_icon'      => '#86efac',
                    'color_rail_active_bg' => '#059669',
                    'color_rail_active_fg' => '#ffffff',
                ],
            ],
            'ember' => [
                'label' => 'Ember',
                'light' => [
                    'color_primary'        => '#dc2626',
                    'color_secondary'      => '#292524',
                    'color_accent'         => '#fb923c',
                    'color_page_bg'        => '#fff7ed',
                    'color_panel_bg'       => '#ffffff',
                    'color_panel_border'   => '#ffedd5',
                    'color_header_bg'      => '#ffffff',
                    'color_header_border'  => '#ffedd5',
                    'color_nav_bg'         => '#ffffff',
                    'color_nav_border'     => '#ffedd5',
                    'color_nav_muted'      => '#fff1f2',
                    'color_nav_active_bg'  => '#fee2e2',
                    'color_nav_active_fg'  => '#991b1b',
                    'color_rail_bg'        => '#fff7ed',
                    'color_rail_border'    => '#fed7aa',
                    'color_rail_icon'      => '#57534e',
                    'color_rail_active_bg' => '#dc2626',
                    'color_rail_active_fg' => '#ffffff',
                ],
                'dark' => [
                    'color_primary'        => '#f87171',
                    'color_secondary'      => '#fff7ed',
                    'color_accent'         => '#fdba74',
                    'color_page_bg'        => '#431407',
                    'color_panel_bg'       => '#7f1d1d',
                    'color_panel_border'   => '#991b1b',
                    'color_header_bg'      => '#7f1d1d',
                    'color_header_border'  => '#991b1b',
                    'color_nav_bg'         => '#7f1d1d',
                    'color_nav_border'     => '#991b1b',
                    'color_nav_muted'      => '#7c2d12',
                    'color_nav_active_bg'  => '#b91c1c',
                    'color_nav_active_fg'  => '#ffffff',
                    'color_rail_bg'        => '#431407',
                    'color_rail_border'    => '#7c2d12',
                    'color_rail_icon'      => '#fdba74',
                    'color_rail_active_bg' => '#ef4444',
                    'color_rail_active_fg' => '#ffffff',
                ],
            ],
            'slate' => [
                'label' => 'Slate',
                'light' => [
                    'color_primary'        => '#334155',
                    'color_secondary'      => '#111827',
                    'color_accent'         => '#0ea5e9',
                    'color_page_bg'        => '#f8fafc',
                    'color_panel_bg'       => '#ffffff',
                    'color_panel_border'   => '#e2e8f0',
                    'color_header_bg'      => '#ffffff',
                    'color_header_border'  => '#e2e8f0',
                    'color_nav_bg'         => '#ffffff',
                    'color_nav_border'     => '#e2e8f0',
                    'color_nav_muted'      => '#f1f5f9',
                    'color_nav_active_bg'  => '#e2e8f0',
                    'color_nav_active_fg'  => '#0f172a',
                    'color_rail_bg'        => '#f8fafc',
                    'color_rail_border'    => '#e2e8f0',
                    'color_rail_icon'      => '#64748b',
                    'color_rail_active_bg' => '#334155',
                    'color_rail_active_fg' => '#ffffff',
                ],
                'dark' => [
                    'color_primary'        => '#cbd5e1',
                    'color_secondary'      => '#f8fafc',
                    'color_accent'         => '#38bdf8',
                    'color_page_bg'        => '#020617',
                    'color_panel_bg'       => '#0f172a',
                    'color_panel_border'   => '#1e293b',
                    'color_header_bg'      => '#0f172a',
                    'color_header_border'  => '#1e293b',
                    'color_nav_bg'         => '#0f172a',
                    'color_nav_border'     => '#1e293b',
                    'color_nav_muted'      => '#111827',
                    'color_nav_active_bg'  => '#334155',
                    'color_nav_active_fg'  => '#f8fafc',
                    'color_rail_bg'        => '#020617',
                    'color_rail_border'    => '#1e293b',
                    'color_rail_icon'      => '#94a3b8',
                    'color_rail_active_bg' => '#334155',
                    'color_rail_active_fg' => '#ffffff',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function singleSidebarPresets(): array
    {
        return [
            'linen' => [
                'label' => 'Linen',
                'light' => [
                    'color_primary'       => '#0f766e',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#f97316',
                    'color_page_bg'       => '#f8fafc',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#e5e7eb',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#e5e7eb',
                    'color_nav_bg'        => '#fafaf9',
                    'color_nav_border'    => '#e7e5e4',
                    'color_nav_muted'     => '#f5f5f4',
                    'color_nav_active_bg' => '#ccfbf1',
                    'color_nav_active_fg' => '#115e59',
                ],
                'dark' => [
                    'color_primary'       => '#5eead4',
                    'color_secondary'     => '#f5f5f4',
                    'color_accent'        => '#fb923c',
                    'color_page_bg'       => '#111827',
                    'color_panel_bg'      => '#1f2937',
                    'color_panel_border'  => '#374151',
                    'color_header_bg'     => '#1f2937',
                    'color_header_border' => '#374151',
                    'color_nav_bg'        => '#111827',
                    'color_nav_border'    => '#374151',
                    'color_nav_muted'     => '#1f2937',
                    'color_nav_active_bg' => '#134e4a',
                    'color_nav_active_fg' => '#ccfbf1',
                ],
            ],
            'indigo' => [
                'label' => 'Indigo',
                'light' => [
                    'color_primary'       => '#4f46e5',
                    'color_secondary'     => '#111827',
                    'color_accent'        => '#ec4899',
                    'color_page_bg'       => '#eef2ff',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#dbeafe',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#dbeafe',
                    'color_nav_bg'        => '#ffffff',
                    'color_nav_border'    => '#dbeafe',
                    'color_nav_muted'     => '#eef2ff',
                    'color_nav_active_bg' => '#e0e7ff',
                    'color_nav_active_fg' => '#312e81',
                ],
                'dark' => [
                    'color_primary'       => '#818cf8',
                    'color_secondary'     => '#eef2ff',
                    'color_accent'        => '#f472b6',
                    'color_page_bg'       => '#1e1b4b',
                    'color_panel_bg'      => '#312e81',
                    'color_panel_border'  => '#4338ca',
                    'color_header_bg'     => '#312e81',
                    'color_header_border' => '#4338ca',
                    'color_nav_bg'        => '#1e1b4b',
                    'color_nav_border'    => '#4338ca',
                    'color_nav_muted'     => '#312e81',
                    'color_nav_active_bg' => '#4338ca',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
            'graphite' => [
                'label' => 'Graphite',
                'light' => [
                    'color_primary'       => '#18181b',
                    'color_secondary'     => '#111827',
                    'color_accent'        => '#0ea5e9',
                    'color_page_bg'       => '#fafafa',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#e4e4e7',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#e4e4e7',
                    'color_nav_bg'        => '#fafafa',
                    'color_nav_border'    => '#e4e4e7',
                    'color_nav_muted'     => '#f4f4f5',
                    'color_nav_active_bg' => '#e4e4e7',
                    'color_nav_active_fg' => '#18181b',
                ],
                'dark' => [
                    'color_primary'       => '#e4e4e7',
                    'color_secondary'     => '#fafafa',
                    'color_accent'        => '#38bdf8',
                    'color_page_bg'       => '#09090b',
                    'color_panel_bg'      => '#18181b',
                    'color_panel_border'  => '#27272a',
                    'color_header_bg'     => '#18181b',
                    'color_header_border' => '#27272a',
                    'color_nav_bg'        => '#09090b',
                    'color_nav_border'    => '#27272a',
                    'color_nav_muted'     => '#18181b',
                    'color_nav_active_bg' => '#27272a',
                    'color_nav_active_fg' => '#fafafa',
                ],
            ],
            'forest' => [
                'label' => 'Forest',
                'light' => [
                    'color_primary'       => '#166534',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#f59e0b',
                    'color_page_bg'       => '#f0fdf4',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#dcfce7',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#dcfce7',
                    'color_nav_bg'        => '#f7fee7',
                    'color_nav_border'    => '#d9f99d',
                    'color_nav_muted'     => '#ecfccb',
                    'color_nav_active_bg' => '#d9f99d',
                    'color_nav_active_fg' => '#365314',
                ],
                'dark' => [
                    'color_primary'       => '#86efac',
                    'color_secondary'     => '#f0fdf4',
                    'color_accent'        => '#fbbf24',
                    'color_page_bg'       => '#052e16',
                    'color_panel_bg'      => '#14532d',
                    'color_panel_border'  => '#166534',
                    'color_header_bg'     => '#14532d',
                    'color_header_border' => '#166534',
                    'color_nav_bg'        => '#14532d',
                    'color_nav_border'    => '#166534',
                    'color_nav_muted'     => '#166534',
                    'color_nav_active_bg' => '#365314',
                    'color_nav_active_fg' => '#f0fdf4',
                ],
            ],
            'rose' => [
                'label' => 'Rose',
                'light' => [
                    'color_primary'       => '#be123c',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#fb7185',
                    'color_page_bg'       => '#fff1f2',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#ffe4e6',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#ffe4e6',
                    'color_nav_bg'        => '#fff1f2',
                    'color_nav_border'    => '#ffe4e6',
                    'color_nav_muted'     => '#ffe4e6',
                    'color_nav_active_bg' => '#fecdd3',
                    'color_nav_active_fg' => '#881337',
                ],
                'dark' => [
                    'color_primary'       => '#fb7185',
                    'color_secondary'     => '#fff1f2',
                    'color_accent'        => '#fda4af',
                    'color_page_bg'       => '#4c0519',
                    'color_panel_bg'      => '#881337',
                    'color_panel_border'  => '#9f1239',
                    'color_header_bg'     => '#881337',
                    'color_header_border' => '#9f1239',
                    'color_nav_bg'        => '#4c0519',
                    'color_nav_border'    => '#9f1239',
                    'color_nav_muted'     => '#881337',
                    'color_nav_active_bg' => '#9f1239',
                    'color_nav_active_fg' => '#fff1f2',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function topbarPresets(): array
    {
        return [
            'orbit' => [
                'label' => 'Orbit',
                'light' => [
                    'color_primary'       => '#17ce91',
                    'color_secondary'     => '#0f172a',
                    'color_accent'        => '#fc8024',
                    'color_page_bg'       => '#f8fafc',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#e2e8f0',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#e2e8f0',
                    'color_nav_bg'        => '#f8fafc',
                    'color_nav_border'    => '#e2e8f0',
                    'color_nav_muted'     => '#e2e8f0',
                    'color_nav_active_bg' => '#ecfdf5',
                    'color_nav_active_fg' => '#0f766e',
                ],
                'dark' => [
                    'color_primary'       => '#2dd4a8',
                    'color_secondary'     => '#f8fafc',
                    'color_accent'        => '#fb923c',
                    'color_page_bg'       => '#020617',
                    'color_panel_bg'      => '#0f172a',
                    'color_panel_border'  => '#1e293b',
                    'color_header_bg'     => '#0f172a',
                    'color_header_border' => '#1e293b',
                    'color_nav_bg'        => '#111827',
                    'color_nav_border'    => '#1f2937',
                    'color_nav_muted'     => '#1e293b',
                    'color_nav_active_bg' => '#064e3b',
                    'color_nav_active_fg' => '#ecfdf5',
                ],
            ],
            'cobalt' => [
                'label' => 'Cobalt',
                'light' => [
                    'color_primary'       => '#2563eb',
                    'color_secondary'     => '#0f172a',
                    'color_accent'        => '#8b5cf6',
                    'color_page_bg'       => '#eff6ff',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#dbeafe',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#dbeafe',
                    'color_nav_bg'        => '#eff6ff',
                    'color_nav_border'    => '#bfdbfe',
                    'color_nav_muted'     => '#dbeafe',
                    'color_nav_active_bg' => '#dbeafe',
                    'color_nav_active_fg' => '#1d4ed8',
                ],
                'dark' => [
                    'color_primary'       => '#60a5fa',
                    'color_secondary'     => '#eff6ff',
                    'color_accent'        => '#c4b5fd',
                    'color_page_bg'       => '#172554',
                    'color_panel_bg'      => '#1d4ed8',
                    'color_panel_border'  => '#1e40af',
                    'color_header_bg'     => '#1e3a8a',
                    'color_header_border' => '#1e40af',
                    'color_nav_bg'        => '#172554',
                    'color_nav_border'    => '#1e40af',
                    'color_nav_muted'     => '#1e3a8a',
                    'color_nav_active_bg' => '#1d4ed8',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
            'sand' => [
                'label' => 'Sand',
                'light' => [
                    'color_primary'       => '#a16207',
                    'color_secondary'     => '#292524',
                    'color_accent'        => '#ea580c',
                    'color_page_bg'       => '#fffbeb',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#fde68a',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#fde68a',
                    'color_nav_bg'        => '#fef3c7',
                    'color_nav_border'    => '#fcd34d',
                    'color_nav_muted'     => '#fde68a',
                    'color_nav_active_bg' => '#fbbf24',
                    'color_nav_active_fg' => '#78350f',
                ],
                'dark' => [
                    'color_primary'       => '#fcd34d',
                    'color_secondary'     => '#fffbeb',
                    'color_accent'        => '#fb923c',
                    'color_page_bg'       => '#451a03',
                    'color_panel_bg'      => '#78350f',
                    'color_panel_border'  => '#92400e',
                    'color_header_bg'     => '#78350f',
                    'color_header_border' => '#92400e',
                    'color_nav_bg'        => '#451a03',
                    'color_nav_border'    => '#92400e',
                    'color_nav_muted'     => '#78350f',
                    'color_nav_active_bg' => '#b45309',
                    'color_nav_active_fg' => '#fffbeb',
                ],
            ],
            'sage' => [
                'label' => 'Sage',
                'light' => [
                    'color_primary'       => '#15803d',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#0ea5e9',
                    'color_page_bg'       => '#f7fee7',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#d9f99d',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#d9f99d',
                    'color_nav_bg'        => '#f7fee7',
                    'color_nav_border'    => '#bef264',
                    'color_nav_muted'     => '#ecfccb',
                    'color_nav_active_bg' => '#dcfce7',
                    'color_nav_active_fg' => '#166534',
                ],
                'dark' => [
                    'color_primary'       => '#86efac',
                    'color_secondary'     => '#f7fee7',
                    'color_accent'        => '#38bdf8',
                    'color_page_bg'       => '#14532d',
                    'color_panel_bg'      => '#166534',
                    'color_panel_border'  => '#15803d',
                    'color_header_bg'     => '#166534',
                    'color_header_border' => '#15803d',
                    'color_nav_bg'        => '#14532d',
                    'color_nav_border'    => '#15803d',
                    'color_nav_muted'     => '#166534',
                    'color_nav_active_bg' => '#15803d',
                    'color_nav_active_fg' => '#f7fee7',
                ],
            ],
            'violet' => [
                'label' => 'Violet',
                'light' => [
                    'color_primary'       => '#7c3aed',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#ec4899',
                    'color_page_bg'       => '#f5f3ff',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#ddd6fe',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#ddd6fe',
                    'color_nav_bg'        => '#f5f3ff',
                    'color_nav_border'    => '#c4b5fd',
                    'color_nav_muted'     => '#ede9fe',
                    'color_nav_active_bg' => '#ddd6fe',
                    'color_nav_active_fg' => '#5b21b6',
                ],
                'dark' => [
                    'color_primary'       => '#c4b5fd',
                    'color_secondary'     => '#f5f3ff',
                    'color_accent'        => '#f472b6',
                    'color_page_bg'       => '#2e1065',
                    'color_panel_bg'      => '#4c1d95',
                    'color_panel_border'  => '#5b21b6',
                    'color_header_bg'     => '#4c1d95',
                    'color_header_border' => '#5b21b6',
                    'color_nav_bg'        => '#2e1065',
                    'color_nav_border'    => '#5b21b6',
                    'color_nav_muted'     => '#4c1d95',
                    'color_nav_active_bg' => '#6d28d9',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function hybridPresets(): array
    {
        return [
            'slate' => [
                'label' => 'Slate',
                'light' => [
                    'color_primary'       => '#334155',
                    'color_secondary'     => '#111827',
                    'color_accent'        => '#0ea5e9',
                    'color_page_bg'       => '#f8fafc',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#e2e8f0',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#e2e8f0',
                    'color_nav_bg'        => '#f8fafc',
                    'color_nav_border'    => '#e2e8f0',
                    'color_nav_muted'     => '#f1f5f9',
                    'color_nav_active_bg' => '#e2e8f0',
                    'color_nav_active_fg' => '#0f172a',
                ],
                'dark' => [
                    'color_primary'       => '#cbd5e1',
                    'color_secondary'     => '#f8fafc',
                    'color_accent'        => '#38bdf8',
                    'color_page_bg'       => '#020617',
                    'color_panel_bg'      => '#0f172a',
                    'color_panel_border'  => '#1e293b',
                    'color_header_bg'     => '#0f172a',
                    'color_header_border' => '#1e293b',
                    'color_nav_bg'        => '#111827',
                    'color_nav_border'    => '#1e293b',
                    'color_nav_muted'     => '#1f2937',
                    'color_nav_active_bg' => '#334155',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
            'sunrise' => [
                'label' => 'Sunrise',
                'light' => [
                    'color_primary'       => '#ea580c',
                    'color_secondary'     => '#292524',
                    'color_accent'        => '#e11d48',
                    'color_page_bg'       => '#fff7ed',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#fed7aa',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#fed7aa',
                    'color_nav_bg'        => '#fff7ed',
                    'color_nav_border'    => '#fdba74',
                    'color_nav_muted'     => '#ffedd5',
                    'color_nav_active_bg' => '#fdba74',
                    'color_nav_active_fg' => '#9a3412',
                ],
                'dark' => [
                    'color_primary'       => '#fdba74',
                    'color_secondary'     => '#fff7ed',
                    'color_accent'        => '#fb7185',
                    'color_page_bg'       => '#431407',
                    'color_panel_bg'      => '#7c2d12',
                    'color_panel_border'  => '#9a3412',
                    'color_header_bg'     => '#7c2d12',
                    'color_header_border' => '#9a3412',
                    'color_nav_bg'        => '#431407',
                    'color_nav_border'    => '#9a3412',
                    'color_nav_muted'     => '#7c2d12',
                    'color_nav_active_bg' => '#c2410c',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
            'teal' => [
                'label' => 'Teal',
                'light' => [
                    'color_primary'       => '#0f766e',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#14b8a6',
                    'color_page_bg'       => '#f0fdfa',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#ccfbf1',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#ccfbf1',
                    'color_nav_bg'        => '#f0fdfa',
                    'color_nav_border'    => '#99f6e4',
                    'color_nav_muted'     => '#ccfbf1',
                    'color_nav_active_bg' => '#99f6e4',
                    'color_nav_active_fg' => '#115e59',
                ],
                'dark' => [
                    'color_primary'       => '#5eead4',
                    'color_secondary'     => '#f0fdfa',
                    'color_accent'        => '#2dd4bf',
                    'color_page_bg'       => '#042f2e',
                    'color_panel_bg'      => '#134e4a',
                    'color_panel_border'  => '#115e59',
                    'color_header_bg'     => '#134e4a',
                    'color_header_border' => '#115e59',
                    'color_nav_bg'        => '#042f2e',
                    'color_nav_border'    => '#115e59',
                    'color_nav_muted'     => '#134e4a',
                    'color_nav_active_bg' => '#0f766e',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
            'violet' => [
                'label' => 'Violet',
                'light' => [
                    'color_primary'       => '#7c3aed',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#ec4899',
                    'color_page_bg'       => '#f5f3ff',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#ddd6fe',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#ddd6fe',
                    'color_nav_bg'        => '#f5f3ff',
                    'color_nav_border'    => '#c4b5fd',
                    'color_nav_muted'     => '#ede9fe',
                    'color_nav_active_bg' => '#ddd6fe',
                    'color_nav_active_fg' => '#5b21b6',
                ],
                'dark' => [
                    'color_primary'       => '#c4b5fd',
                    'color_secondary'     => '#f5f3ff',
                    'color_accent'        => '#f472b6',
                    'color_page_bg'       => '#2e1065',
                    'color_panel_bg'      => '#4c1d95',
                    'color_panel_border'  => '#5b21b6',
                    'color_header_bg'     => '#4c1d95',
                    'color_header_border' => '#5b21b6',
                    'color_nav_bg'        => '#2e1065',
                    'color_nav_border'    => '#5b21b6',
                    'color_nav_muted'     => '#4c1d95',
                    'color_nav_active_bg' => '#6d28d9',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
            'moss' => [
                'label' => 'Moss',
                'light' => [
                    'color_primary'       => '#4d7c0f',
                    'color_secondary'     => '#1f2937',
                    'color_accent'        => '#84cc16',
                    'color_page_bg'       => '#f7fee7',
                    'color_panel_bg'      => '#ffffff',
                    'color_panel_border'  => '#d9f99d',
                    'color_header_bg'     => '#ffffff',
                    'color_header_border' => '#d9f99d',
                    'color_nav_bg'        => '#f7fee7',
                    'color_nav_border'    => '#bef264',
                    'color_nav_muted'     => '#ecfccb',
                    'color_nav_active_bg' => '#d9f99d',
                    'color_nav_active_fg' => '#3f6212',
                ],
                'dark' => [
                    'color_primary'       => '#bef264',
                    'color_secondary'     => '#f7fee7',
                    'color_accent'        => '#a3e635',
                    'color_page_bg'       => '#1a2e05',
                    'color_panel_bg'      => '#365314',
                    'color_panel_border'  => '#4d7c0f',
                    'color_header_bg'     => '#365314',
                    'color_header_border' => '#4d7c0f',
                    'color_nav_bg'        => '#1a2e05',
                    'color_nav_border'    => '#4d7c0f',
                    'color_nav_muted'     => '#365314',
                    'color_nav_active_bg' => '#65a30d',
                    'color_nav_active_fg' => '#ffffff',
                ],
            ],
        ];
    }

    public function register(): void
    {
        $this->app->singleton(ConfigRegistry::class, fn () => new ConfigRegistry);
        $this->app->singleton(LayoutThemeRegistry::class, fn () => new LayoutThemeRegistry);
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $this->registerDefaultGroups();
            $this->registerPermissionsAndMenu();
        });
    }

    /**
     * Core built-in configuration groups (SEO / Appearance / Document / Media).
     */
    protected function registerDefaultGroups(): void
    {
        // Localization -----------------------------------------------------
        Config::registerGroup('Localization', 950, [
            'icon'        => 'bs.translate',
            'description' => 'Admin interface language and translatable content locales.',
        ]);
        Config::registerItem('Localization', 'locale.default', 'select', config('orbit.locale.default', 'ko'), 'default', [
            'title'   => 'Default admin language',
            'options' => Locale::all(),
        ]);
        Config::registerItem('Localization', 'locale.fallback', 'select', config('orbit.locale.fallback', 'en'), 'default', [
            'title'   => 'Fallback language',
            'options' => Locale::all(),
        ]);
        Config::registerItem('Localization', 'locale.supported', 'multiselect', config('orbit.locale.supported', ['ko', 'en']), 'default', [
            'title'   => 'Available admin languages',
            'options' => Locale::all(),
        ]);
        Config::registerItem('Localization', 'locale.content', 'multiselect', config('orbit.locale.content', ['ko', 'en']), 'default', [
            'title'   => 'Content locales',
            'options' => Locale::all(),
        ]);

        // SEO --------------------------------------------------------------
        Config::registerGroup('SEO', 900, [
            'icon'        => 'bs.search',
            'description' => 'Search engine optimisation defaults applied to all content.',
        ]);
        Config::registerItem('SEO', 'seo.site_title', 'input', config('app.name'), 'default', ['title' => 'Site title']);
        Config::registerItem('SEO', 'seo.title_separator', 'input', '|', 'default', ['title' => 'Title separator']);
        Config::registerItem('SEO', 'seo.site_description', 'textarea', null, 'default', ['title' => 'Site description']);
        Config::registerItem('SEO', 'seo.default_thumbnail', 'attach', null, 'default', ['title' => 'Default share image']);
        Config::registerItem('SEO', 'seo.snippet', 'textarea', null, 'default', ['title' => 'Search snippet / meta defaults']);
        Config::registerItem('SEO', 'seo.robots', 'select', 'index,follow', 'default', [
            'title'   => 'Default robots policy',
            'options' => ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'],
        ]);

        // Admin design (branding + layout theme) --------------------------------
        Config::registerGroup('Admin Design', 810, [
            'icon'        => 'bs.palette',
            'title'       => '관리자 디자인설정',
            'description' => 'Admin panel branding, layout mode and per-layout colours.',
        ]);
        Config::registerSection('Admin Design', 'identity', ['title' => 'Identity', 'priority' => 40]);
        Config::registerItem('Admin Design', 'branding.name', 'input', config('app.name'), 'identity', ['title' => '관리자 페이지 이름']);
        Config::registerItem('Admin Design', 'branding.logo', 'attach', '/vendor/orbit/SVG/logo.svg', 'identity', ['title' => '로고']);
        Config::registerItem('Admin Design', 'branding.symbol', 'attach', '/vendor/orbit/SVG/symbol.svg', 'identity', ['title' => '아이콘 마크']);
        Config::registerItem('Admin Design', 'branding.favicon', 'attach', '/vendor/orbit/favicon/favicon.ico', 'identity', ['title' => '파비콘']);
        Config::registerItem('Admin Design', 'branding.dark_mode', 'switcher', false, 'identity', ['title' => '다크 모드 사용']);
        Config::registerSection('Admin Design', 'colors', ['title' => 'Default colours', 'priority' => 35]);
        Config::registerItem('Admin Design', 'branding.palette', 'select', 'orbit', 'colors', [
            'title'   => 'Palette preset',
            'options' => ['orbit', 'midnight', 'forest', 'sunset', 'custom'],
        ]);
        Config::registerItem('Admin Design', 'branding.color_primary', 'color', '#17ce91', 'colors', ['title' => 'Primary']);
        Config::registerItem('Admin Design', 'branding.color_secondary', 'color', '#64748b', 'colors', ['title' => 'Secondary']);
        Config::registerItem('Admin Design', 'branding.color_accent', 'color', '#fc8024', 'colors', ['title' => 'Accent']);
        Config::registerSection('Admin Design', 'layout', ['title' => 'Layout', 'priority' => 30]);
        Config::registerItem('Admin Design', 'layout.mode', 'select', 'palette-split', 'layout', [
            'title'   => 'Active layout',
            'options' => self::LAYOUT_MODES,
        ]);

        $themeRegistry = app(LayoutThemeRegistry::class);
        $themeRegistry->registerDefaults(self::LAYOUT_MODES, self::builtInLayoutThemes());
        $themeRegistry->registerConfigItems();

        // Document --------------------------------------------------------
        Config::registerGroup('Document', 700, [
            'icon'        => 'bs.file-earmark-text',
            'description' => 'Default behaviour for document-based content types.',
        ]);
        Config::registerItem('Document', 'document.default_approved', 'select', 30, 'default', [
            'title'   => 'Default approval state',
            'options' => [0 => 'Rejected', 10 => 'Waiting', 30 => 'Approved'],
        ]);
        Config::registerItem('Document', 'document.allow_comments', 'switcher', true, 'default', ['title' => 'Allow comments by default']);
        Config::registerItem('Document', 'document.use_division', 'switcher', false, 'default', ['title' => 'Enable division (schema flag)']);
        Config::registerItem('Document', 'document.use_revision', 'switcher', false, 'default', ['title' => 'Enable revisions (schema flag)']);

        // Media -----------------------------------------------------------
        Config::registerGroup('Media', 600, [
            'icon'        => 'bs.images',
            'description' => 'Image and video processing for the media library.',
        ]);
        Config::registerSection('Media', 'image', ['title' => 'Images', 'priority' => 20]);
        Config::registerSection('Media', 'video', ['title' => 'Video', 'priority' => 10]);
        Config::registerItem('Media', 'media.image_max_width', 'number', 1200, 'image', ['title' => 'Max image width (px)']);
        Config::registerItem('Media', 'media.image_quality', 'number', 100, 'image', ['title' => 'Image quality (1-100)']);
        Config::registerItem('Media', 'media.video_resolution', 'select', '720p', 'video', [
            'title'   => 'Target resolution',
            'options' => ['480p', '720p', '1080p'],
        ]);
        Config::registerItem('Media', 'media.video_bitrate', 'input', '2500k', 'video', ['title' => 'Video bitrate']);
        Config::registerItem('Media', 'media.video_format', 'select', 'mp4', 'video', [
            'title'   => 'Container format',
            'options' => ['mp4', 'webm'],
        ]);
        Config::registerItem('Media', 'media.video_thumbnail', 'switcher', true, 'video', ['title' => 'Generate poster thumbnail']);
    }

    /**
     * Submit the settings hub permission, per-group permissions and menu.
     */
    protected function registerPermissionsAndMenu(): void
    {
        $registry = app(ConfigRegistry::class);

        $group = ItemPermission::group(__('Settings'))
            ->addPermission('orbit.configs', __('Settings'));

        foreach ($registry->getGroups() as $configGroup) {
            $group->addPermission($configGroup->getPermission(), $configGroup->getTitle());
        }

        Orbit::registerPermission($group);

        $url = Route::has('orbit.configs') ? route('orbit.configs') : '#';

        Orbit::registerMenuElement(
            Menu::make(__('Settings'))
                ->icon('bs.gear')
                ->url($url)
                ->sort(9000)
                ->set('section', __('System'))
                ->set('permission', 'orbit.configs')
        );
    }
}
