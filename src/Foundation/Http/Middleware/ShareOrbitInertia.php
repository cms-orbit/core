<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Middleware;

use Closure;
use CmsOrbit\Core\Alert\Alert;
use CmsOrbit\Core\Alert\Toast;
use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Config\LayoutThemeRegistry;
use CmsOrbit\Core\Foundation\Notifications\DashboardMessage;
use CmsOrbit\Core\Foundation\Notifications\OrbitMessage;
use CmsOrbit\Core\Foundation\Providers\ConfigServiceProvider;
use CmsOrbit\Core\Support\Facades\Orbit;
use CmsOrbit\Core\Support\Locale;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects the Inertia root view and shared props consumed by the Orbit React
 * admin shell (menu, permissions, branding, flash, notifications, i18n).
 *
 * This lives in the package so a plain Laravel host does not need to author any
 * Inertia glue: installing `cms-orbit/core` is enough for the admin panel to
 * render with a populated shell. It is attached to the Orbit route groups, so
 * host applications keep their own global Inertia middleware untouched.
 */
class ShareOrbitInertia
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::setRootView('orbit::orbit.app');

        Inertia::share([
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(config('orbit.guard')),
            ],
            'orbit' => fn (): array => $this->orbitShared($request),
        ]);

        return $next($request);
    }

    /**
     * Shared props consumed by the Orbit React admin shell. Populated from the
     * Orbit menu/permission registries and the Appearance config group. All
     * DB-backed lookups are guarded so the app boots before migration.
     *
     * @return array<string, mixed>
     */
    protected function orbitShared(Request $request): array
    {
        $user = $request->user(config('orbit.guard'));

        return [
            'menu' => $this->safe(fn () => Orbit::getMenu(), []),
            'sections' => $this->safe(fn () => Orbit::getSections(), []),
            'permissions' => $user?->getAttribute('permissions') ?? [],
            'home' => Route::has('orbit.main') ? route('orbit.main') : '/main',
            'user' => $this->orbitUser($user),
            'flash' => $this->orbitFlash($request),
            'brand' => $this->safe(fn () => $this->orbitBrand($request), $this->orbitBrandDefaults()),
            'notifications' => $this->safe(fn () => $this->orbitNotifications($user), []),
            'media' => $this->safe(fn () => $this->orbitMediaEndpoints(), null),
            'i18n' => $this->safe(fn () => $this->orbitI18n(), $this->orbitI18nDefaults()),
        ];
    }

    /**
     * @return array{id: mixed, name: mixed, email: mixed, avatarUrl: ?string}|null
     */
    protected function orbitUser(?Authenticatable $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'avatarUrl' => $this->resolveOrbitUserAvatarUrl($user),
        ];
    }

    protected function resolveOrbitUserAvatarUrl(Authenticatable $user): ?string
    {
        foreach (['profilePhotoUrl', 'avatarUrl', 'getProfilePhotoUrl', 'getAvatarUrl'] as $method) {
            if (method_exists($user, $method)) {
                $value = $user->{$method}();

                if (is_string($value) && filled($value)) {
                    return $value;
                }
            }
        }

        foreach (['profile_photo_url', 'avatar_url'] as $attribute) {
            $value = $user->getAttribute($attribute);

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        foreach (['avatar', 'profile_photo', 'profile_photo_id', 'avatar_id'] as $attribute) {
            $attachment = $this->resolveAttachment($user->getAttribute($attribute));

            if ($attachment !== null) {
                return $attachment->url();
            }
        }

        return $this->generatedOrbitAvatar(
            (string) ($user->getAttribute('name') ?: $user->getAttribute('email') ?: 'Orbit User')
        );
    }

    protected function generatedOrbitAvatar(string $label): string
    {
        $initials = collect(preg_split('/\s+/u', trim($label)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        $initials = $initials !== '' ? $initials : 'O';
        $background = sprintf('#%06X', crc32($label) & 0xFFFFFF);
        $escapedInitials = htmlspecialchars($initials, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="{$escapedInitials}">
  <rect width="64" height="64" rx="32" fill="{$background}" />
  <text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" fill="#ffffff" font-family="Inter, Arial, sans-serif" font-size="24" font-weight="700">{$escapedInitials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
    }

    /**
     * Surface flashed Toast/Alert notifications to the React shell.
     *
     * @return array{message: ?string, type: ?string}
     */
    protected function orbitFlash(Request $request): array
    {
        $session = $request->session();

        $message = $session->get(Toast::SESSION_MESSAGE)
            ?? $session->get(Alert::SESSION_MESSAGE);

        $type = $session->get(Toast::SESSION_LEVEL)
            ?? $session->get(Alert::SESSION_LEVEL);

        return [
            'message' => $message,
            'type' => $type,
        ];
    }

    /**
     * Localization payload consumed by the React client-side translator.
     *
     * @return array<string, mixed>
     */
    protected function orbitI18n(): array
    {
        $available = [];

        foreach (Locale::supported() as $code) {
            $available[] = ['code' => $code, 'label' => Locale::label($code)];
        }

        return [
            'locale' => app()->getLocale(),
            'messages' => Locale::messages(),
            'available' => $available,
            'switchUrl' => Route::has('orbit.locale.switch') ? route('orbit.locale.switch') : null,
        ];
    }

    /**
     * Safe fallback used before the locale registry / routes are available.
     *
     * @return array<string, mixed>
     */
    protected function orbitI18nDefaults(): array
    {
        return [
            'locale' => app()->getLocale(),
            'messages' => [],
            'available' => [],
            'switchUrl' => null,
        ];
    }

    /**
     * Recent Orbit notifications for the notification center.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function orbitNotifications(?Authenticatable $user): array
    {
        if ($user === null || ! method_exists($user, 'notifications')) {
            return [];
        }

        return $user->notifications()
            ->whereIn('type', [OrbitMessage::class, DashboardMessage::class])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? null,
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['action'] ?? null,
                'time' => optional($notification->getAttribute('created_at'))->diffForHumans(),
                'read' => $notification->getAttribute('read_at') !== null,
            ])
            ->all();
    }

    /**
     * Resolve the media library endpoints consumed by the React picker.
     *
     * @return array{index: string, upload: string, remove: string}
     */
    protected function orbitMediaEndpoints(): array
    {
        return [
            'index' => route('orbit.media.index'),
            'upload' => route('orbit.media.upload'),
            'remove' => route('orbit.media.index'),
        ];
    }

    /**
     * Branding tokens injected as CSS variables by the React shell.
     *
     * @return array<string, mixed>
     */
    protected function orbitBrand(?Request $request = null): array
    {
        $request ??= request();
        $defaults = $this->orbitBrandDefaults();
        $registry = app(LayoutThemeRegistry::class);

        $mode = (string) orbit_config('layout.mode', $defaults['layout']);
        $definition = $registry->getThemes()[$mode] ?? null;
        $dualTone = $definition['dualTone'] ?? false;
        $legacyDarkMode = (bool) orbit_config('branding.dark_mode', false);
        $configuredThemeMode = (string) orbit_config('branding.theme_mode', $legacyDarkMode ? 'dark' : $defaults['themeMode']);
        $themeToggleEnabled = (bool) orbit_config('branding.theme_toggle_enabled', $defaults['themeToggleEnabled']);
        $themeMode = $this->resolveEffectiveThemeMode($request, $configuredThemeMode, $themeToggleEnabled, $defaults['themeMode']);
        $tone = $this->resolveActiveThemeTone($request, $themeMode, $themeToggleEnabled);

        $activeTokens = $registry->resolveColors($mode, $tone);
        $lightTokens = $dualTone ? $registry->resolveColors($mode, 'light') : [];
        $darkTokens = $dualTone ? $registry->resolveColors($mode, 'dark') : [];

        $colors = [
            'primary' => $activeTokens['color_primary'] ?? $defaults['colors']['primary'],
            'secondary' => $activeTokens['color_secondary'] ?? $defaults['colors']['secondary'],
            'accent' => $activeTokens['color_accent'] ?? $defaults['colors']['accent'],
            'surface' => $activeTokens['color_panel_bg'] ?? '#ffffff',
            'muted' => $activeTokens['color_nav_muted'] ?? '#f1f5f9',
        ];

        $brand = [
            'name' => orbit_config('branding.name', $defaults['name']),
            'logo' => $this->resolveBrandAsset(orbit_config('branding.logo', $defaults['logo']), $defaults['logo']),
            'logoDark' => $this->resolveBrandAsset(orbit_config('branding.logo_dark'), $defaults['logo']),
            'symbol' => $this->resolveBrandAsset(orbit_config('branding.symbol', $defaults['symbol']), $defaults['symbol']),
            'symbolDark' => $this->resolveBrandAsset(orbit_config('branding.symbol_dark'), $defaults['symbol']),
            'favicon' => $this->resolveBrandAsset(orbit_config('branding.favicon', $defaults['favicon']), $defaults['favicon']),
            'faviconVariants' => $this->resolveFaviconVariants(orbit_config('branding.favicon')),
            'themeMode' => in_array($themeMode, ['system', 'light', 'dark'], true) ? $themeMode : $defaults['themeMode'],
            'themeToggleEnabled' => $themeToggleEnabled,
            'palette' => (string) orbit_config("theme.{$mode}.palette", 'orbit'),
            'layout' => $mode,
            'contentWidth' => ConfigServiceProvider::normalizeContentWidth(
                orbit_config('layout.content_width', $defaults['contentWidth'])
            ),
            'colors' => $colors,
            'tokens' => $activeTokens,
            'activeTone' => $tone,
        ];

        if ($dualTone) {
            $brand['tokenSchemes'] = [
                'light' => $lightTokens,
                'dark' => $darkTokens,
            ];
        }

        return $brand;
    }

    protected function resolveEffectiveThemeMode(
        Request $request,
        string $configuredThemeMode,
        bool $themeToggleEnabled,
        string $fallbackThemeMode,
    ): string {
        if ($themeToggleEnabled) {
            $cookieMode = $request->cookie('orbit_theme_mode');

            if (in_array($cookieMode, ['system', 'light', 'dark'], true)) {
                return $cookieMode;
            }
        }

        return in_array($configuredThemeMode, ['system', 'light', 'dark'], true)
            ? $configuredThemeMode
            : $fallbackThemeMode;
    }

    protected function resolveActiveThemeTone(
        Request $request,
        string $themeMode,
        bool $themeToggleEnabled,
    ): string {
        if ($themeToggleEnabled) {
            $resolvedTone = $request->cookie('orbit_theme_resolved');

            if (in_array($resolvedTone, ['light', 'dark'], true)) {
                return $resolvedTone;
            }
        }

        return $themeMode === 'dark' ? 'dark' : 'light';
    }

    /**
     * Default Orbit branding used before any branding config is stored.
     *
     * @return array<string, mixed>
     */
    protected function orbitBrandDefaults(): array
    {
        return [
            'name' => config('app.name'),
            'logo' => '/vendor/orbit/SVG/logo.svg',
            'logoDark' => '/vendor/orbit/SVG/logo.svg',
            'symbol' => '/vendor/orbit/SVG/symbol.svg',
            'symbolDark' => '/vendor/orbit/SVG/symbol.svg',
            'favicon' => '/vendor/orbit/favicon/favicon.ico',
            'themeMode' => 'light',
            'themeToggleEnabled' => true,
            'palette' => 'orbit',
            'layout' => 'palette-split',
            'contentWidth' => 'default',
            'colors' => [
                'primary' => '#17ce91',
                'secondary' => '#64748b',
                'accent' => '#fc8024',
            ],
        ];
    }

    protected function resolveBrandAsset(mixed $value, ?string $fallback = null): ?string
    {
        if (is_string($value) && filled($value)) {
            return $value;
        }

        $attachment = $this->resolveAttachment($value);

        return $attachment?->url() ?? $fallback;
    }

    /**
     * @return array<string, string|null>|null
     */
    protected function resolveFaviconVariants(mixed $value): ?array
    {
        $attachment = $this->resolveAttachment($value);

        if ($attachment === null) {
            return null;
        }

        $meta = is_array($attachment->meta) ? $attachment->meta : [];
        $variants = $meta['favicon_variants'] ?? null;

        return is_array($variants) ? $variants : null;
    }

    protected function resolveAttachment(mixed $value): ?Attachment
    {
        $id = null;

        if (is_array($value)) {
            if (array_key_exists('id', $value)) {
                $id = $value['id'] ?? null;
            } else {
                $first = $value[0] ?? null;

                if (is_array($first)) {
                    $id = $first['id'] ?? null;
                } elseif (is_scalar($first)) {
                    $id = $first;
                }
            }
        } elseif (is_object($value)) {
            $id = $value->id ?? null;
        } elseif (is_scalar($value)) {
            $id = $value;
        }

        if ($id === null || $id === '') {
            return null;
        }

        return Orbit::model(Attachment::class)::find($id);
    }

    /**
     * Run a closure, swallowing failures (e.g. missing tables pre-migration).
     *
     * @template T
     *
     * @param  Closure():T  $callback
     * @param  T  $default
     * @return T
     */
    protected function safe(Closure $callback, $default)
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return $default;
        }
    }
}
