import type { LayoutMode } from './branding';

export interface LayoutThemeToken {
    key: string;
    label: string;
    group?: string;
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
export interface LayoutPreviewColors {
    primary: string;
    secondary: string;
    accent: string;
    surface: string;
    muted: string;
    pageBg: string;
    panelBg: string;
    panelBorder: string;
    headerBg: string;
    headerBorder: string;
    navBg: string;
    navBorder: string;
    navMuted: string;
    navSectionFg: string;
    navGroupFg: string;
    navSectionBorder: string;
    navActiveBg: string;
    navActiveFg: string;
    railBg: string;
    railBorder: string;
    railIcon: string;
    railActiveBg: string;
    railActiveFg: string;
}

/** Map a layout token map to the preview colours used by shell snippets. */
export function toPreviewColors(tokens: Record<string, string>): LayoutPreviewColors {
    const panelBg = tokens.color_panel_bg ?? '#ffffff';
    const navBg = tokens.color_nav_bg ?? panelBg;

    return {
        primary: tokens.color_primary ?? '#10b981',
        secondary: tokens.color_secondary ?? tokens.color_muted ?? '#0f172a',
        accent: tokens.color_accent ?? '#f59e0b',
        surface: panelBg,
        muted: tokens.color_nav_muted ?? '#f1f5f9',
        pageBg: tokens.color_page_bg ?? '#f8fafc',
        panelBg,
        panelBorder: tokens.color_panel_border ?? '#e2e8f0',
        headerBg: tokens.color_header_bg ?? '#ffffff',
        headerBorder: tokens.color_header_border ?? tokens.color_panel_border ?? '#e2e8f0',
        navBg,
        navBorder: tokens.color_nav_border ?? tokens.color_panel_border ?? '#e2e8f0',
        navMuted: tokens.color_nav_muted ?? '#f1f5f9',
        navSectionFg: tokens.color_nav_section_fg ?? tokens.color_secondary ?? '#334155',
        navGroupFg: tokens.color_nav_group_fg ?? tokens.color_secondary ?? '#64748b',
        navSectionBorder: tokens.color_nav_section_border ?? tokens.color_nav_border ?? '#e2e8f0',
        navActiveBg: tokens.color_nav_active_bg ?? '#d1fae5',
        navActiveFg: tokens.color_nav_active_fg ?? '#047857',
        railBg: tokens.color_rail_bg ?? navBg,
        railBorder: tokens.color_rail_border ?? tokens.color_nav_border ?? '#e2e8f0',
        railIcon: tokens.color_rail_icon ?? tokens.color_secondary ?? '#64748b',
        railActiveBg: tokens.color_rail_active_bg ?? tokens.color_nav_active_bg ?? '#d1fae5',
        railActiveFg: tokens.color_rail_active_fg ?? '#047857',
    };
}
