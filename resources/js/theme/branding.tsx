import { useEffect, useMemo, useState } from 'react';
import { isTransparentColor, resolveOpaqueColor } from '../lib/color-value';

export interface BrandColors {
    primary?: string;
    secondary?: string;
    accent?: string;
    surface?: string;
    muted?: string;
}

export type DarkModeSetting = boolean | 'system' | 'light' | 'dark';
export type ThemeModeOption = 'system' | 'light' | 'dark';
export type ContentWidthOption = 'full' | 'default' | 'wide' | 'xwide' | 'contained';
export type ResolvedContentWidthOption = 'full' | 'default' | 'wide' | 'xwide';
export const ORBIT_THEME_MODE_EVENT = 'orbit:theme-mode-changed';
export const ORBIT_THEME_MODE_STORAGE_KEY = 'orbit.theme-mode';
export const ORBIT_THEME_MODE_COOKIE_KEY = 'orbit_theme_mode';
export const ORBIT_THEME_RESOLVED_COOKIE_KEY = 'orbit_theme_resolved';
export type ThemeTone = 'light' | 'dark';

export type LayoutMode = 'palette-split' | 'sidebar-single' | 'topbar' | 'hybrid';

export const CONTENT_WIDTH_LABELS: Record<ResolvedContentWidthOption, string> = {
    full: '전체 폭',
    default: '기본',
    wide: '크게',
    xwide: '매우 크게',
};

export function normalizeContentWidth(
    contentWidth: ContentWidthOption | null | undefined,
): ResolvedContentWidthOption {
    if (contentWidth === 'full' || contentWidth === 'wide' || contentWidth === 'xwide') {
        return contentWidth;
    }

    return 'default';
}

export interface OrbitBrand {
    name?: string;
    logo?: string | null;
    logoDark?: string | null;
    /** Square mark used in the compact icon rail. */
    symbol?: string | null;
    symbolDark?: string | null;
    favicon?: string | null;
    faviconVariants?: Record<string, string | null> | null;
    /** Named preset palette; explicit `colors` override individual tokens. */
    palette?: string | null;
    colors?: BrandColors | null;
    /** Default theme mode shared from the backend config. */
    themeMode?: DarkModeSetting | null;
    /** Whether the user-facing light/dark/system switcher is enabled. */
    themeToggleEnabled?: boolean | null;
    darkMode?: DarkModeSetting | null;
    /** Active admin layout mode; colours are resolved per mode server-side. */
    layout?: LayoutMode | null;
    /** Shared content width policy for the admin shell. */
    contentWidth?: ContentWidthOption | null;
    /** Current active theme token set (`color_*` keys from the backend registry). */
    tokens?: Record<string, string> | null;
    /** Optional light/dark token sets for dual-tone layouts. */
    tokenSchemes?: {
        light?: Record<string, string> | null;
        dark?: Record<string, string> | null;
    };
    /** Effective light/dark tone resolved server-side for SSR hydration. */
    activeTone?: ThemeTone | null;
}

/** Built-in palette presets. Backend exposes the same names in branding config. */
export const PALETTE_PRESETS: Record<string, Required<BrandColors>> = {
    orbit: {
        primary: '#17ce91',
        secondary: '#64748b',
        accent: '#fc8024',
        surface: '#ffffff',
        muted: '#ecfdf5',
    },
    'apple-light': {
        primary: '#2563eb',
        secondary: '#111827',
        accent: '#06b6d4',
        surface: '#ffffff',
        muted: '#e8eef8',
    },
    simple: {
        primary: '#4f46e5',
        secondary: '#0f172a',
        accent: '#8b5cf6',
        surface: '#ffffff',
        muted: '#eef2ff',
    },
    amuz: {
        primary: '#6366f1',
        secondary: '#1e293b',
        accent: '#06b6d4',
        surface: '#ffffff',
        muted: '#e0e7ff',
    },
    slate: {
        primary: '#475569',
        secondary: '#0f172a',
        accent: '#0ea5e9',
        surface: '#ffffff',
        muted: '#f1f5f9',
    },
    'studio-rose': {
        primary: '#ff385c',
        secondary: '#111827',
        accent: '#fb7185',
        surface: '#ffffff',
        muted: '#fff1f2',
    },
    'clover-mint': {
        primary: '#03c75a',
        secondary: '#0f172a',
        accent: '#14b8a6',
        surface: '#ffffff',
        muted: '#ecfdf5',
    },
    'violet-pop': {
        primary: '#8b5cf6',
        secondary: '#111827',
        accent: '#ec4899',
        surface: '#ffffff',
        muted: '#f5f3ff',
    },
    custom: {
        primary: '#17ce91',
        secondary: '#0f172a',
        accent: '#fc8024',
        surface: '#ffffff',
        muted: '#ecfdf5',
    },
};

const DEFAULT_PALETTE = PALETTE_PRESETS.orbit;

/** Shade → [lightness, chroma] constants, ported from Filament's Color::generatePalette(). */
const SHADE_CONSTANTS: Record<number, [number, number]> = {
    50: [0.97717647058824, 0.01395454545455],
    100: [0.95035294117647, 0.03272727272727],
    200: [0.90547058823529, 0.06318181818182],
    300: [0.84047058823529, 0.10604545454546],
    400: [0.75352941176471, 0.15027272727273],
    500: [0.68270588235294, 0.17009090909091],
    600: [0.59782352941176, 0.16913636363636],
    700: [0.51494117647059, 0.14940909090909],
    800: [0.44611764705882, 0.12331818181818],
    900: [0.39458823529412, 0.09963636363636],
    950: [0.27788235294118, 0.07136363636364],
};

export const SHADE_KEYS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as const;

/** Parse `#rgb`/`#rrggbb`/`rgb(r,g,b)` into normalised sRGB components (0–1). */
function parseSrgb(color: string): [number, number, number] | null {
    const value = color.trim().replace(/\s+/g, '');

    const hexMatch = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

    if (hexMatch) {
        let hex = hexMatch[1];

        if (hex.length === 3) {
            hex = hex.split('').map((c) => c + c).join('');
        }

        const int = parseInt(hex, 16);

        return [((int >> 16) & 255) / 255, ((int >> 8) & 255) / 255, (int & 255) / 255];
    }

    const rgbMatch = value.match(/^rgba?\((\d+),(\d+),(\d+)/i);

    if (rgbMatch) {
        return [Number(rgbMatch[1]) / 255, Number(rgbMatch[2]) / 255, Number(rgbMatch[3]) / 255];
    }

    return null;
}

/** Convert an sRGB colour to OKLCH `[lightness, chroma, hue°]` (Filament algorithm). */
function srgbToOklch(color: string): [number, number, number] | null {
    const parsed = parseSrgb(color);

    if (!parsed) {
        return null;
    }

    const linearize = (channel: number) =>
        channel <= 0.04045 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);

    const [red, green, blue] = parsed.map(linearize) as [number, number, number];

    const long = 0.4122214708 * red + 0.5363325363 * green + 0.0514459929 * blue;
    const medium = 0.2119034982 * red + 0.6806995451 * green + 0.1073969566 * blue;
    const short = 0.0883024619 * red + 0.2817188376 * green + 0.6299787005 * blue;

    const longCubeRoot = Math.cbrt(long);
    const mediumCubeRoot = Math.cbrt(medium);
    const shortCubeRoot = Math.cbrt(short);

    const lightness =
        0.2104542553 * longCubeRoot + 0.793617785 * mediumCubeRoot - 0.0040720468 * shortCubeRoot;
    const opponentA =
        1.9779984951 * longCubeRoot - 2.428592205 * mediumCubeRoot + 0.4505937099 * shortCubeRoot;
    const opponentB =
        0.0259040371 * longCubeRoot + 0.7827717662 * mediumCubeRoot - 0.808675766 * shortCubeRoot;

    const chroma = Math.sqrt(opponentA * opponentA + opponentB * opponentB);
    let hue = (Math.atan2(opponentB, opponentA) * 180) / Math.PI;

    if (hue < 0) {
        hue += 360;
    }

    return [lightness, chroma, hue];
}

/**
 * Generate an 11-step OKLCH shade palette from a single base colour, matching
 * Filament's Color::generatePalette(): fixed lightness/chroma per shade, base
 * hue preserved (chroma dropped for near-grey inputs).
 */
export function generateShades(color: string): Record<number, string> {
    const oklch = srgbToOklch(color);
    const shades: Record<number, string> = {};

    if (!oklch) {
        SHADE_KEYS.forEach((shade) => {
            shades[shade] = color;
        });

        return shades;
    }

    const [, chroma, hue] = oklch;
    const isAchromatic = chroma < 0.03;
    const roundedHue = Math.round(hue * 1000) / 1000;

    SHADE_KEYS.forEach((shade) => {
        const [lightness, shadeChroma] = SHADE_CONSTANTS[shade];
        const l = Math.round(lightness * 1000) / 1000;
        const c = isAchromatic ? 0 : Math.round(shadeChroma * 1000) / 1000;
        shades[shade] = `oklch(${l} ${c} ${roundedHue})`;
    });

    return shades;
}

export function resolveBrandColors(brand: OrbitBrand | undefined): Required<BrandColors> {
    const preset =
        (brand?.palette && PALETTE_PRESETS[brand.palette]) || DEFAULT_PALETTE;

    return {
        primary: brand?.colors?.primary ?? preset.primary,
        secondary: brand?.colors?.secondary ?? preset.secondary,
        accent: brand?.colors?.accent ?? preset.accent,
        surface: brand?.colors?.surface ?? '#ffffff',
        muted: brand?.colors?.muted ?? '#f1f5f9',
    };
}

export function resolveThemeTokensForTone(
    brand: OrbitBrand | undefined,
    tone: ThemeTone,
): Record<string, string> {
    const scheme = brand?.tokenSchemes?.[tone];

    if (scheme) {
        return scheme;
    }

    return brand?.tokens ?? {};
}

function readDocumentThemeTone(): ThemeTone {
    if (typeof document === 'undefined') {
        return 'light';
    }

    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

function syncThemeModeCookies(mode: ThemeModeOption, resolvedTone: ThemeTone): void {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = '31536000';
    const base = `path=/; max-age=${maxAge}; SameSite=Lax`;

    document.cookie = `${ORBIT_THEME_MODE_COOKIE_KEY}=${mode}; ${base}`;
    document.cookie = `${ORBIT_THEME_RESOLVED_COOKIE_KEY}=${resolvedTone}; ${base}`;
}

function isDarkDocument(): boolean {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
}

export function resolveBrandAsset(
    brand: OrbitBrand | undefined,
    kind: 'logo' | 'symbol',
): string | null | undefined {
    const useDarkAsset = isDarkDocument();

    if (kind === 'logo') {
        return useDarkAsset ? brand?.logoDark ?? brand?.logo : brand?.logo ?? brand?.logoDark;
    }

    return useDarkAsset ? brand?.symbolDark ?? brand?.symbol : brand?.symbol ?? brand?.symbolDark;
}

export function normalizeThemeMode(setting: DarkModeSetting | null | undefined): ThemeModeOption {
    if (setting === 'dark' || setting === true) {
        return 'dark';
    }

    if (setting === 'light' || setting === false) {
        return 'light';
    }

    return 'system';
}

export function readStoredThemeMode(): ThemeModeOption | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const stored = window.localStorage.getItem(ORBIT_THEME_MODE_STORAGE_KEY);

    return stored === 'system' || stored === 'light' || stored === 'dark' ? stored : null;
}

export function resolveThemeMode(
    setting: DarkModeSetting | null | undefined,
    allowUserToggle = true,
): ThemeModeOption {
    if (!allowUserToggle) {
        return normalizeThemeMode(setting);
    }

    return readStoredThemeMode() ?? normalizeThemeMode(setting);
}

export function storeThemeMode(mode: ThemeModeOption): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(ORBIT_THEME_MODE_STORAGE_KEY, mode);
    applyDarkMode(mode, true);
    syncThemeModeCookies(mode, readDocumentThemeTone());
    window.dispatchEvent(new CustomEvent(ORBIT_THEME_MODE_EVENT, { detail: mode }));
}

export function applyDarkMode(
    setting: DarkModeSetting | null | undefined,
    allowUserToggle = true,
    syncCookies = false,
) {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const mode = resolveThemeMode(setting, allowUserToggle);

    if (mode === 'system') {
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
        root.classList.toggle('dark', Boolean(prefersDark));

        if (syncCookies) {
            syncThemeModeCookies(mode, prefersDark ? 'dark' : 'light');
        }

        return;
    }

    root.classList.toggle('dark', mode === 'dark');

    if (syncCookies) {
        syncThemeModeCookies(mode, mode === 'dark' ? 'dark' : 'light');
    }
}

function upsertHeadLink(selector: string, attrs: Record<string, string>) {
    let link = document.head.querySelector<HTMLLinkElement>(selector);

    if (!link) {
        link = document.createElement('link');
        document.head.appendChild(link);
    }

    Object.entries(attrs).forEach(([key, value]) => {
        link?.setAttribute(key, value);
    });
}

function applyFavicon(
    favicon: string | null | undefined,
    faviconVariants: Record<string, string | null> | null | undefined,
) {
    if (typeof document === 'undefined' || (!favicon && !faviconVariants)) {
        return;
    }

    const fallbackIcon = faviconVariants?.ico ?? faviconVariants?.icon32 ?? favicon;

    if (fallbackIcon) {
        upsertHeadLink('link[rel="icon"]', { rel: 'icon', href: fallbackIcon });
    }

    if (faviconVariants?.appleTouch) {
        upsertHeadLink('link[rel="apple-touch-icon"]', {
            rel: 'apple-touch-icon',
            href: faviconVariants.appleTouch,
        });
    }

    if (faviconVariants?.icon192) {
        upsertHeadLink('link[sizes="192x192"]', {
            rel: 'icon',
            type: 'image/png',
            sizes: '192x192',
            href: faviconVariants.icon192,
        });
    }

    if (faviconVariants?.icon512) {
        upsertHeadLink('link[sizes="512x512"]', {
            rel: 'icon',
            type: 'image/png',
            sizes: '512x512',
            href: faviconVariants.icon512,
        });
    }
}

/**
 * Reads the shared `orbit.brand` prop and injects CSS variables / dark-mode
 * class at runtime so the admin theme follows branding settings. Returns the
 * style object to apply on the shell root so descendants inherit tokens.
 */
export function useBrandTheme(brand: OrbitBrand | undefined): React.CSSProperties {
    const serverTone: ThemeTone = brand?.activeTone === 'dark' ? 'dark' : 'light';
    const [activeTone, setActiveTone] = useState<ThemeTone>(serverTone);
    const [hasHydrated, setHasHydrated] = useState(false);
    const colors = useMemo(() => resolveBrandColors(brand), [brand]);
    const themeToggleEnabled = brand?.themeToggleEnabled ?? true;
    const themeModeSetting = brand?.themeMode ?? brand?.darkMode;
    const effectiveTone = hasHydrated ? activeTone : serverTone;
    const tokens = useMemo(
        () => resolveThemeTokensForTone(brand, effectiveTone),
        [brand, effectiveTone],
    );

    useEffect(() => {
        applyDarkMode(themeModeSetting, themeToggleEnabled, true);
        setActiveTone(readDocumentThemeTone());
        setHasHydrated(true);
    }, [themeModeSetting, themeToggleEnabled]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const syncActiveTone = () => {
            setActiveTone(readDocumentThemeTone());
        };
        const media = window.matchMedia?.('(prefers-color-scheme: dark)');
        const handleMediaChange = () => {
            if (resolveThemeMode(themeModeSetting, themeToggleEnabled) === 'system') {
                applyDarkMode(themeModeSetting, themeToggleEnabled, true);
                syncActiveTone();
            }
        };

        window.addEventListener(ORBIT_THEME_MODE_EVENT, syncActiveTone);
        media?.addEventListener?.('change', handleMediaChange);

        return () => {
            window.removeEventListener(ORBIT_THEME_MODE_EVENT, syncActiveTone);
            media?.removeEventListener?.('change', handleMediaChange);
        };
    }, [themeModeSetting, themeToggleEnabled]);

    useEffect(() => {
        applyFavicon(brand?.favicon, brand?.faviconVariants);
    }, [brand?.favicon, brand?.faviconVariants]);

    return useMemo(() => {
        const shadeColors = {
            primary: resolveOpaqueColor(tokens.color_primary, colors.primary),
            secondary: resolveOpaqueColor(tokens.color_secondary, colors.secondary),
            accent: resolveOpaqueColor(tokens.color_accent, colors.accent),
        };
        const style: Record<string, string> = {
            '--color-orbit-primary': tokens.color_primary ?? colors.primary,
            '--color-orbit-secondary': tokens.color_secondary ?? colors.secondary,
            '--color-orbit-accent': tokens.color_accent ?? colors.accent,
            '--color-orbit-surface': colors.surface ?? '#ffffff',
            '--color-orbit-muted': colors.muted ?? '#f1f5f9',
        };

        Object.entries(tokens).forEach(([key, value]) => {
            if (value === undefined || value === null || value === '') {
                return;
            }

            style[`--color-orbit-${key.replace(/^color_/, '').replace(/_/g, '-')}`] = isTransparentColor(value)
                ? 'transparent'
                : value;
        });

        style['--color-orbit-nav-section-fg'] =
            tokens.color_nav_section_fg ?? tokens.color_secondary ?? colors.secondary;
        style['--color-orbit-nav-group-fg'] =
            tokens.color_nav_group_fg ?? tokens.color_secondary ?? colors.secondary;
        style['--color-orbit-nav-section-bg'] =
            tokens.color_nav_section_bg ?? tokens.color_nav_muted ?? colors.muted;
        style['--color-orbit-nav-section-border'] =
            tokens.color_nav_section_border ?? tokens.color_nav_border ?? '#e2e8f0';
        style['--color-orbit-table-row-border'] =
            tokens.color_table_row_border ?? tokens.color_nav_muted ?? colors.muted ?? '#f1f5f9';

        (['primary', 'secondary', 'accent'] as const).forEach((token) => {
            const shades = generateShades(shadeColors[token]);
            SHADE_KEYS.forEach((shade) => {
                style[`--color-orbit-${token}-${shade}`] = shades[shade];
            });
        });

        return style as React.CSSProperties;
    }, [colors, tokens]);
}
