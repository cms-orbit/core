import { useEffect, useMemo, useState } from 'react';

export interface BrandColors {
    primary?: string;
    secondary?: string;
    accent?: string;
    surface?: string;
    muted?: string;
}

export type DarkModeSetting = boolean | 'system' | 'light' | 'dark';
export type ThemeModeOption = 'system' | 'light' | 'dark';
export const ORBIT_THEME_MODE_EVENT = 'orbit:theme-mode-changed';
export const ORBIT_THEME_MODE_STORAGE_KEY = 'orbit.theme-mode';

export type LayoutMode = 'palette-split' | 'sidebar-single' | 'topbar' | 'hybrid';

export interface OrbitBrand {
    name?: string;
    logo?: string | null;
    /** Square mark used in the compact icon rail. */
    symbol?: string | null;
    favicon?: string | null;
    /** Named preset palette; explicit `colors` override individual tokens. */
    palette?: string | null;
    colors?: BrandColors | null;
    darkMode?: DarkModeSetting | null;
    /** Active admin layout mode; colours are resolved per mode server-side. */
    layout?: LayoutMode | null;
    /** Current active theme token set (`color_*` keys from the backend registry). */
    tokens?: Record<string, string> | null;
    /** Optional light/dark token sets for dual-tone layouts. */
    tokenSchemes?: {
        light?: Record<string, string> | null;
        dark?: Record<string, string> | null;
    };
}

/** Built-in palette presets. Backend exposes the same names in branding config. */
export const PALETTE_PRESETS: Record<string, Required<BrandColors>> = {
    orbit: {
        primary: '#17ce91',
        secondary: '#64748b',
        accent: '#fc8024',
        surface: '#ffffff',
        muted: '#f1f5f9',
    },
    midnight: {
        primary: '#4f46e5',
        secondary: '#64748b',
        accent: '#ec4899',
        surface: '#ffffff',
        muted: '#eef2ff',
    },
    forest: {
        primary: '#059669',
        secondary: '#475569',
        accent: '#f59e0b',
        surface: '#ffffff',
        muted: '#ecfdf5',
    },
    sunset: {
        primary: '#e11d48',
        secondary: '#475569',
        accent: '#f97316',
        surface: '#ffffff',
        muted: '#fff1f2',
    },
    custom: {
        primary: '#17ce91',
        secondary: '#64748b',
        accent: '#fc8024',
        surface: '#ffffff',
        muted: '#f1f5f9',
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

function resolveActiveThemeTokens(brand: OrbitBrand | undefined): Record<string, string> {
    if (brand?.tokenSchemes && typeof document !== 'undefined') {
        const isDark = document.documentElement.classList.contains('dark');
        const scheme = isDark ? brand.tokenSchemes.dark : brand.tokenSchemes.light;

        if (scheme) {
            return scheme;
        }
    }

    return brand?.tokens ?? {};
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

export function resolveThemeMode(setting: DarkModeSetting | null | undefined): ThemeModeOption {
    return readStoredThemeMode() ?? normalizeThemeMode(setting);
}

export function storeThemeMode(mode: ThemeModeOption): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(ORBIT_THEME_MODE_STORAGE_KEY, mode);
    window.dispatchEvent(new CustomEvent(ORBIT_THEME_MODE_EVENT, { detail: mode }));
}

export function applyDarkMode(setting: DarkModeSetting | null | undefined) {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const mode = normalizeThemeMode(setting);

    if (mode === 'system') {
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
        root.classList.toggle('dark', Boolean(prefersDark));

        return;
    }

    root.classList.toggle('dark', mode === 'dark');
}

function applyFavicon(favicon: string | null | undefined) {
    if (typeof document === 'undefined' || !favicon) {
        return;
    }

    let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');

    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }

    link.href = favicon;
}

/**
 * Reads the shared `orbit.brand` prop and injects CSS variables / dark-mode
 * class at runtime so the admin theme follows branding settings. Returns the
 * style object to apply on the shell root so descendants inherit tokens.
 */
export function useBrandTheme(brand: OrbitBrand | undefined): React.CSSProperties {
    const [, setThemeModeRevision] = useState(0);
    const [, setToneRevision] = useState(0);
    const colors = useMemo(() => resolveBrandColors(brand), [brand]);
    const tokens = resolveActiveThemeTokens(brand);
    const themeMode = resolveThemeMode(brand?.darkMode);

    useEffect(() => {
        applyDarkMode(themeMode);
    }, [themeMode]);

    useEffect(() => {
        if (!brand?.tokenSchemes || typeof document === 'undefined') {
            return;
        }

        const root = document.documentElement;
        const observer = new MutationObserver(() => {
            setToneRevision((value) => value + 1);
        });

        observer.observe(root, { attributes: true, attributeFilter: ['class'] });

        return () => observer.disconnect();
    }, [brand?.tokenSchemes]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const handleThemeModeChange = () => {
            setThemeModeRevision((value) => value + 1);
        };
        const media = window.matchMedia?.('(prefers-color-scheme: dark)');
        const handleMediaChange = () => {
            if (resolveThemeMode(brand?.darkMode) === 'system') {
                setThemeModeRevision((value) => value + 1);
            }
        };

        window.addEventListener(ORBIT_THEME_MODE_EVENT, handleThemeModeChange);
        media?.addEventListener?.('change', handleMediaChange);

        return () => {
            window.removeEventListener(ORBIT_THEME_MODE_EVENT, handleThemeModeChange);
            media?.removeEventListener?.('change', handleMediaChange);
        };
    }, [brand?.darkMode]);

    useEffect(() => {
        applyFavicon(brand?.favicon);
    }, [brand?.favicon]);

    return useMemo(() => {
        const style: Record<string, string> = {
            '--color-orbit-primary': colors.primary,
            '--color-orbit-secondary': colors.secondary,
            '--color-orbit-accent': colors.accent,
            '--color-orbit-surface': colors.surface ?? '#ffffff',
            '--color-orbit-muted': colors.muted ?? '#f1f5f9',
        };

        Object.entries(tokens).forEach(([key, value]) => {
            style[`--color-orbit-${key.replace(/^color_/, '').replace(/_/g, '-')}`] = value;
        });

        (['primary', 'secondary', 'accent'] as const).forEach((token) => {
            const shades = generateShades(colors[token]);
            SHADE_KEYS.forEach((shade) => {
                style[`--color-orbit-${token}-${shade}`] = shades[shade];
            });
        });

        return style as React.CSSProperties;
    }, [colors, tokens]);
}
