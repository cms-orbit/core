<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Foundation\ItemPermission;
use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Support\Facades\Config;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Config registry, Core default config groups, and submits the
 * per-group permissions + Settings menu to Core.
 */
class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfigRegistry::class, fn () => new ConfigRegistry);
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
        // SEO --------------------------------------------------------------
        Config::registerGroup('SEO', 900, [
            'icon' => 'bs.search',
            'description' => 'Search engine optimisation defaults applied to all content.',
        ]);
        Config::registerItem('SEO', 'seo.site_title', 'input', config('app.name'), 'default', ['title' => 'Site title']);
        Config::registerItem('SEO', 'seo.title_separator', 'input', '|', 'default', ['title' => 'Title separator']);
        Config::registerItem('SEO', 'seo.site_description', 'textarea', null, 'default', ['title' => 'Site description']);
        Config::registerItem('SEO', 'seo.default_thumbnail', 'attach', null, 'default', ['title' => 'Default share image']);
        Config::registerItem('SEO', 'seo.snippet', 'textarea', null, 'default', ['title' => 'Search snippet / meta defaults']);
        Config::registerItem('SEO', 'seo.robots', 'select', 'index,follow', 'default', [
            'title' => 'Default robots policy',
            'options' => ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'],
        ]);

        // Appearance / Branding -------------------------------------------
        Config::registerGroup('Appearance', 800, [
            'icon' => 'bs.palette',
            'description' => 'Admin panel branding, colours and dark mode.',
        ]);
        Config::registerSection('Appearance', 'identity', ['title' => 'Identity', 'priority' => 30]);
        Config::registerSection('Appearance', 'colors', ['title' => 'Colours', 'priority' => 20]);
        Config::registerItem('Appearance', 'branding.name', 'input', config('app.name'), 'identity', ['title' => 'Brand name']);
        Config::registerItem('Appearance', 'branding.logo', 'attach', '/vendor/orbit/SVG/logo.svg', 'identity', ['title' => 'Logo']);
        Config::registerItem('Appearance', 'branding.symbol', 'attach', '/vendor/orbit/SVG/symbol.svg', 'identity', ['title' => 'Icon mark']);
        Config::registerItem('Appearance', 'branding.favicon', 'attach', '/vendor/orbit/favicon/favicon.ico', 'identity', ['title' => 'Favicon']);
        Config::registerItem('Appearance', 'branding.dark_mode', 'switcher', false, 'identity', ['title' => 'Enable dark mode']);
        Config::registerItem('Appearance', 'branding.palette', 'select', 'orbit', 'colors', [
            'title' => 'Palette preset',
            'options' => ['orbit', 'midnight', 'forest', 'sunset', 'custom'],
        ]);
        Config::registerItem('Appearance', 'branding.color_primary', 'color', '#17ce91', 'colors', ['title' => 'Primary']);
        Config::registerItem('Appearance', 'branding.color_secondary', 'color', '#64748b', 'colors', ['title' => 'Secondary']);
        Config::registerItem('Appearance', 'branding.color_accent', 'color', '#fc8024', 'colors', ['title' => 'Accent']);

        // Document --------------------------------------------------------
        Config::registerGroup('Document', 700, [
            'icon' => 'bs.file-earmark-text',
            'description' => 'Default behaviour for document-based content types.',
        ]);
        Config::registerItem('Document', 'document.default_approved', 'select', 30, 'default', [
            'title' => 'Default approval state',
            'options' => [0 => 'Rejected', 10 => 'Waiting', 30 => 'Approved'],
        ]);
        Config::registerItem('Document', 'document.allow_comments', 'switcher', true, 'default', ['title' => 'Allow comments by default']);
        Config::registerItem('Document', 'document.use_division', 'switcher', false, 'default', ['title' => 'Enable division (schema flag)']);
        Config::registerItem('Document', 'document.use_revision', 'switcher', false, 'default', ['title' => 'Enable revisions (schema flag)']);

        // Media -----------------------------------------------------------
        Config::registerGroup('Media', 600, [
            'icon' => 'bs.images',
            'description' => 'Image and video processing for the media library.',
        ]);
        Config::registerSection('Media', 'image', ['title' => 'Images', 'priority' => 20]);
        Config::registerSection('Media', 'video', ['title' => 'Video', 'priority' => 10]);
        Config::registerItem('Media', 'media.image_max_width', 'number', 1200, 'image', ['title' => 'Max image width (px)']);
        Config::registerItem('Media', 'media.image_quality', 'number', 100, 'image', ['title' => 'Image quality (1-100)']);
        Config::registerItem('Media', 'media.video_resolution', 'select', '720p', 'video', [
            'title' => 'Target resolution',
            'options' => ['480p', '720p', '1080p'],
        ]);
        Config::registerItem('Media', 'media.video_bitrate', 'input', '2500k', 'video', ['title' => 'Video bitrate']);
        Config::registerItem('Media', 'media.video_format', 'select', 'mp4', 'video', [
            'title' => 'Container format',
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
