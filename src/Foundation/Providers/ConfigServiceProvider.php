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
     * Available content-width levels for the Orbit admin shell.
     *
     * @var array<string, string>
     */
    public const CONTENT_WIDTH_OPTIONS = [
        'full'    => '전체 폭',
        'default' => '기본',
        'wide'    => '크게',
        'xwide'   => '매우 크게',
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
            'branding.logo_dark',
            'branding.symbol',
            'branding.symbol_dark',
            'branding.favicon',
            'branding.theme_toggle_enabled',
            'branding.theme_mode',
            'layout.mode',
            'layout.content_width',
            ...self::designSettingThemeFieldKeys(),
        ];
    }

    public static function normalizeContentWidth(mixed $value): string
    {
        return match ((string) $value) {
            'full', 'wide', 'xwide' => (string) $value,
            'contained'             => 'default',
            default                 => 'default',
        };
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
            self::token('color_nav_section_fg', 'Navigation section text', 'Navigation'),
            self::token('color_nav_group_fg', 'Navigation group text', 'Navigation'),
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
     * @return array{primary: string, secondary: string, accent: string, page: string, panel: string, line: string, nav: string, muted: string, active: string, activeFg: string, section: string, group: string}
     */
    protected static function toneFamily(
        string $primary,
        string $secondary,
        string $accent,
        string $page,
        string $panel,
        string $line,
        string $nav,
        string $muted,
        string $active,
        string $activeFg,
        string $section,
        string $group,
    ): array {
        return [
            'primary'   => $primary,
            'secondary' => $secondary,
            'accent'    => $accent,
            'page'      => $page,
            'panel'     => $panel,
            'line'      => $line,
            'nav'       => $nav,
            'muted'     => $muted,
            'active'    => $active,
            'activeFg'  => $activeFg,
            'section'   => $section,
            'group'     => $group,
        ];
    }

    /**
     * @return array{primary: string, secondary: string, accent: string, page: string, panel: string, line: string, nav: string, muted: string, active: string, activeFg: string, section: string, group: string}
     */
    protected static function lightPointFamily(
        string $primary,
        string $accent,
        string $muted,
        string $active,
        string $activeFg,
    ): array {
        return self::toneFamily(
            $primary,
            '#0f172a',
            $accent,
            '#f8fafc',
            '#ffffff',
            '#e2e8f0',
            '#ffffff',
            $muted,
            $active,
            $activeFg,
            '#334155',
            '#64748b',
        );
    }

    /**
     * @return array{primary: string, secondary: string, accent: string, page: string, panel: string, line: string, nav: string, muted: string, active: string, activeFg: string, section: string, group: string}
     */
    protected static function darkPointFamily(
        string $primary,
        string $accent,
        string $muted,
        string $active,
        string $activeFg,
    ): array {
        return self::toneFamily(
            $primary,
            '#f8fafc',
            $accent,
            '#020617',
            '#0f172a',
            '#1e293b',
            '#0b1120',
            $muted,
            $active,
            $activeFg,
            '#e2e8f0',
            '#94a3b8',
        );
    }

    /**
     * Shared baseline colour families used across every shell layout.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function layoutThemeFamilies(): array
    {
        return [
            'orbit' => [
                'label' => 'Orbit',
                'light' => self::lightPointFamily(
                    '#17ce91',
                    '#fc8024',
                    '#ecfdf5',
                    '#d1fae5',
                    '#047857',
                ),
                'dark' => self::darkPointFamily(
                    '#34d399',
                    '#fb923c',
                    '#1f2937',
                    '#065f46',
                    '#ecfdf5',
                ),
            ],
            'apple-light' => [
                'label' => 'Apple light',
                'light' => self::lightPointFamily(
                    '#2563eb',
                    '#06b6d4',
                    '#e8eef8',
                    '#dbeafe',
                    '#1d4ed8',
                ),
                'dark' => self::darkPointFamily(
                    '#60a5fa',
                    '#67e8f9',
                    '#172033',
                    '#1d4ed8',
                    '#eff6ff',
                ),
            ],
            'simple' => [
                'label' => 'Simple',
                'light' => self::lightPointFamily(
                    '#4f46e5',
                    '#8b5cf6',
                    '#eef2ff',
                    '#e0e7ff',
                    '#3730a3',
                ),
                'dark' => self::darkPointFamily(
                    '#818cf8',
                    '#c084fc',
                    '#1e1b4b',
                    '#3730a3',
                    '#eef2ff',
                ),
            ],
            'amuz' => [
                'label' => 'Amuz',
                'light' => self::lightPointFamily(
                    '#6366f1',
                    '#06b6d4',
                    '#e0e7ff',
                    '#c7d2fe',
                    '#3730a3',
                ),
                'dark' => self::darkPointFamily(
                    '#818cf8',
                    '#22d3ee',
                    '#1e293b',
                    '#312e81',
                    '#e0e7ff',
                ),
            ],
            'slate' => [
                'label' => 'Slate',
                'light' => self::lightPointFamily(
                    '#475569',
                    '#0ea5e9',
                    '#f1f5f9',
                    '#e2e8f0',
                    '#0f172a',
                ),
                'dark' => self::darkPointFamily(
                    '#cbd5e1',
                    '#38bdf8',
                    '#1f2937',
                    '#334155',
                    '#f8fafc',
                ),
            ],
            'studio-rose' => [
                'label' => 'Studio rose',
                'light' => self::lightPointFamily(
                    '#ff385c',
                    '#fb7185',
                    '#fff1f2',
                    '#ffe4e6',
                    '#be123c',
                ),
                'dark' => self::darkPointFamily(
                    '#fb7185',
                    '#fda4af',
                    '#2b1320',
                    '#be123c',
                    '#fff1f2',
                ),
            ],
            'clover-mint' => [
                'label' => 'Clover mint',
                'light' => self::lightPointFamily(
                    '#03c75a',
                    '#14b8a6',
                    '#ecfdf5',
                    '#dcfce7',
                    '#15803d',
                ),
                'dark' => self::darkPointFamily(
                    '#22c55e',
                    '#2dd4bf',
                    '#0f2f24',
                    '#166534',
                    '#ecfdf5',
                ),
            ],
            'violet-pop' => [
                'label' => 'Violet pop',
                'light' => self::lightPointFamily(
                    '#8b5cf6',
                    '#ec4899',
                    '#f5f3ff',
                    '#ede9fe',
                    '#6d28d9',
                ),
                'dark' => self::darkPointFamily(
                    '#a78bfa',
                    '#f472b6',
                    '#2e1065',
                    '#6d28d9',
                    '#faf5ff',
                ),
            ],
        ];
    }

    /**
     * @param array{primary: string, secondary: string, accent: string, page: string, panel: string, line: string, nav: string, muted: string, active: string, activeFg: string, section: string, group: string} $tone
     *
     * @return array<string, string>
     */
    protected static function layoutTone(string $layout, array $tone): array
    {
        $colors = [
            'color_primary'        => $tone['primary'],
            'color_secondary'      => $tone['secondary'],
            'color_accent'         => $tone['accent'],
            'color_page_bg'        => $tone['page'],
            'color_panel_bg'       => $tone['panel'],
            'color_panel_border'   => $tone['line'],
            'color_header_bg'      => $tone['panel'],
            'color_header_border'  => $tone['line'],
            'color_nav_bg'         => $layout === 'palette-split' ? $tone['panel'] : $tone['nav'],
            'color_nav_border'     => $tone['line'],
            'color_nav_muted'      => $tone['muted'],
            'color_nav_section_fg' => $tone['section'],
            'color_nav_group_fg'   => $tone['group'],
            'color_nav_active_bg'  => $tone['active'],
            'color_nav_active_fg'  => $tone['activeFg'],
        ];

        if ($layout === 'palette-split') {
            $colors['color_rail_bg'] = $tone['nav'];
            $colors['color_rail_border'] = $tone['line'];
            $colors['color_rail_icon'] = $tone['group'];
            $colors['color_rail_active_bg'] = $tone['active'];
            $colors['color_rail_active_fg'] = $tone['activeFg'];
        }

        return $colors;
    }

    /**
     * @param array{label: string, light: array<string, string>, dark: array<string, string>} $family
     *
     * @return array{label: string, light: array<string, string>, dark: array<string, string>}
     */
    protected static function layoutPreset(string $layout, array $family): array
    {
        return [
            'label' => $family['label'],
            'light' => self::layoutTone($layout, $family['light']),
            'dark'  => self::layoutTone($layout, $family['dark']),
        ];
    }

    /**
     * @param list<string> $order
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function layoutPresetCollection(string $layout, array $order): array
    {
        $families = self::layoutThemeFamilies();
        $presets = [];

        foreach ($order as $family) {
            $presets[$family] = self::layoutPreset($layout, $families[$family]);
        }

        return $presets;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function paletteSplitPresets(): array
    {
        return self::layoutPresetCollection('palette-split', [
            'orbit',
            'apple-light',
            'simple',
            'studio-rose',
            'clover-mint',
            'amuz',
            'slate',
            'violet-pop',
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function singleSidebarPresets(): array
    {
        return self::layoutPresetCollection('sidebar-single', [
            'simple',
            'apple-light',
            'orbit',
            'clover-mint',
            'studio-rose',
            'amuz',
            'slate',
            'violet-pop',
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function topbarPresets(): array
    {
        return self::layoutPresetCollection('topbar', [
            'apple-light',
            'simple',
            'studio-rose',
            'clover-mint',
            'orbit',
            'amuz',
            'slate',
            'violet-pop',
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function hybridPresets(): array
    {
        return self::layoutPresetCollection('hybrid', [
            'amuz',
            'orbit',
            'simple',
            'studio-rose',
            'clover-mint',
            'apple-light',
            'slate',
            'violet-pop',
        ]);
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
        Config::registerItem('Admin Design', 'branding.logo_dark', 'attach', '/vendor/orbit/SVG/logo.svg', 'identity', ['title' => '다크 모드 로고']);
        Config::registerItem('Admin Design', 'branding.symbol', 'attach', '/vendor/orbit/SVG/symbol.svg', 'identity', ['title' => '아이콘 마크']);
        Config::registerItem('Admin Design', 'branding.symbol_dark', 'attach', '/vendor/orbit/SVG/symbol.svg', 'identity', ['title' => '다크 모드 아이콘 마크']);
        Config::registerItem('Admin Design', 'branding.favicon', 'attach', '/vendor/orbit/favicon/favicon.ico', 'identity', ['title' => '파비콘']);
        Config::registerItem('Admin Design', 'branding.theme_toggle_enabled', 'switcher', true, 'identity', ['title' => '라이트/다크 전환 허용']);
        Config::registerItem('Admin Design', 'branding.theme_mode', 'select', 'light', 'identity', [
            'title'   => '기본 테마 모드',
            'options' => [
                'light'  => '라이트',
                'dark'   => '다크',
                'system' => '시스템',
            ],
        ]);
        Config::registerSection('Admin Design', 'colors', ['title' => 'Default colours', 'priority' => 35]);
        Config::registerItem('Admin Design', 'branding.palette', 'select', 'orbit', 'colors', [
            'title'   => 'Palette preset',
            'options' => [
                'orbit'       => 'Orbit',
                'apple-light' => 'Apple light',
                'simple'      => 'Simple',
                'amuz'        => 'Amuz',
                'slate'       => 'Slate',
                'studio-rose' => 'Studio rose',
                'clover-mint' => 'Clover mint',
                'violet-pop'  => 'Violet pop',
                'custom'      => 'Custom',
            ],
        ]);
        Config::registerItem('Admin Design', 'branding.color_primary', 'color', '#17ce91', 'colors', ['title' => 'Primary']);
        Config::registerItem('Admin Design', 'branding.color_secondary', 'color', '#64748b', 'colors', ['title' => 'Secondary']);
        Config::registerItem('Admin Design', 'branding.color_accent', 'color', '#fc8024', 'colors', ['title' => 'Accent']);
        Config::registerSection('Admin Design', 'layout', ['title' => 'Layout', 'priority' => 30]);
        Config::registerItem('Admin Design', 'layout.mode', 'select', 'palette-split', 'layout', [
            'title'   => 'Active layout',
            'options' => self::LAYOUT_MODES,
        ]);
        Config::registerItem('Admin Design', 'layout.content_width', 'select', 'default', 'layout', [
            'title'   => '컨텐츠 폭',
            'options' => self::CONTENT_WIDTH_OPTIONS,
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

        Orbit::registerSection('system', 'bs.gear', __('Settings'), 9000, [
            'rail'    => 'bottom',
            'sidebar' => 'bottom',
            'topbar'  => 'right',
            'url'     => $url,
        ]);

        Orbit::registerMenuElement(
            Menu::make(__('Settings'))
                ->icon('bs.gear')
                ->url($url)
                ->sort(9000)
                ->set('section', __('Settings'))
                ->set('sectionKey', 'system')
                ->set('permission', 'orbit.configs')
        );
    }
}
