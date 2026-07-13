<!DOCTYPE html>
@php
    $orbitThemeMode = data_get($page ?? [], 'props.orbit.brand.themeMode', 'light');
    $themeToggleEnabled = (bool) data_get($page ?? [], 'props.orbit.brand.themeToggleEnabled', true);
    $activeTone = data_get($page ?? [], 'props.orbit.brand.activeTone', 'light');
    $hasDarkAppearance = ($appearance ?? 'system') === 'dark';
    $isDarkByDefault = $themeToggleEnabled
        ? $activeTone === 'dark'
        : ($orbitThemeMode === 'dark' || ($orbitThemeMode === 'system' && $hasDarkAppearance));
    $orbitPageBg = data_get($page ?? [], 'props.orbit.brand.tokens.color_page_bg');
@endphp
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => $isDarkByDefault, 'orbit-admin' => filled($orbitPageBg)])
    @if (filled($orbitPageBg))
        style="--color-orbit-page-bg: {{ e($orbitPageBg) }}; background-color: var(--color-orbit-page-bg, #f8fafc);"
    @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <script>
            (function () {
                const root = document.documentElement;
                const defaultMode = @json($orbitThemeMode);
                const allowUserToggle = @json($themeToggleEnabled);
                const storageKey = 'orbit.theme-mode';
                const modeCookieKey = 'orbit_theme_mode';
                const resolvedCookieKey = 'orbit_theme_resolved';
                const stored = allowUserToggle ? window.localStorage.getItem(storageKey) : null;
                const mode = ['system', 'light', 'dark'].includes(stored ?? '') ? stored : defaultMode;
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = mode === 'dark' || (mode === 'system' && prefersDark);
                const resolvedTone = isDark ? 'dark' : 'light';
                const maxAge = '31536000';
                const base = 'path=/; max-age=' + maxAge + '; SameSite=Lax';

                root.classList.toggle('dark', isDark);
                document.cookie = modeCookieKey + '=' + mode + '; ' + base;
                document.cookie = resolvedCookieKey + '=' + resolvedTone + '; ' + base;
            })();
        </script>

        @viteReactRefresh
        @vite(['resources/css/orbit.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Orbit') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
