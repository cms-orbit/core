import type { LayoutMode } from './branding';

export interface LayoutThemeToken {
    key: string;
    label: string;
}

export interface LayoutThemePreset {
    label: string;
    colors?: Record<string, string>;
    light?: Record<string, string>;
    dark?: Record<string, string>;
}

export interface LayoutThemeDefinition {
    dualTone?: boolean;
    tokens: LayoutThemeToken[];
    presets: Record<string, LayoutThemePreset>;
}

export type LayoutThemes = Partial<Record<LayoutMode, LayoutThemeDefinition>>;

/** Map a layout token map to the three preview colours used by shell snippets. */
export function toPreviewColors(tokens: Record<string, string>): {
    primary: string;
    secondary: string;
    accent: string;
} {
    return {
        primary: tokens.color_primary ?? '#17ce91',
        secondary: tokens.color_secondary ?? tokens.color_muted ?? '#64748b',
        accent: tokens.color_accent ?? '#fc8024',
    };
}
