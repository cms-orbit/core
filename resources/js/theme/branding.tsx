import { useEffect, useMemo } from 'react';

export interface BrandColors {
    primary?: string;
    secondary?: string;
    accent?: string;
}

export type DarkModeSetting = boolean | 'system';

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
}

/** Built-in palette presets. Backend exposes the same names in branding config. */
export const PALETTE_PRESETS: Record<string, Required<BrandColors>> = {
    orbit: { primary: '#17ce91', secondary: '#64748b', accent: '#fc8024' },
    indigo: { primary: '#4f46e5', secondary: '#64748b', accent: '#ec4899' },
    blue: { primary: '#2563eb', secondary: '#475569', accent: '#06b6d4' },
    emerald: { primary: '#059669', secondary: '#475569', accent: '#f59e0b' },
    rose: { primary: '#e11d48', secondary: '#475569', accent: '#8b5cf6' },
    violet: { primary: '#7c3aed', secondary: '#64748b', accent: '#f43f5e' },
    slate: { primary: '#0f172a', secondary: '#64748b', accent: '#3b82f6' },
};

const DEFAULT_PALETTE = PALETTE_PRESETS.orbit;

export function resolveBrandColors(brand: OrbitBrand | undefined): Required<BrandColors> {
    const preset =
        (brand?.palette && PALETTE_PRESETS[brand.palette]) || DEFAULT_PALETTE;

    return {
        primary: brand?.colors?.primary ?? preset.primary,
        secondary: brand?.colors?.secondary ?? preset.secondary,
        accent: brand?.colors?.accent ?? preset.accent,
    };
}

function applyDarkMode(setting: DarkModeSetting | null | undefined) {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    if (setting === 'system' || setting === null || setting === undefined) {
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
        root.classList.toggle('dark', Boolean(prefersDark));

        return;
    }

    root.classList.toggle('dark', Boolean(setting));
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
    const colors = useMemo(() => resolveBrandColors(brand), [brand]);

    useEffect(() => {
        applyDarkMode(brand?.darkMode);
    }, [brand?.darkMode]);

    useEffect(() => {
        applyFavicon(brand?.favicon);
    }, [brand?.favicon]);

    return useMemo(
        () =>
            ({
                '--color-orbit-primary': colors.primary,
                '--color-orbit-secondary': colors.secondary,
                '--color-orbit-accent': colors.accent,
            }) as React.CSSProperties,
        [colors],
    );
}
