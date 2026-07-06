import { useState } from 'react';
import type { FieldNode, LayoutComponentProps } from '../contract';
import { ColorField } from '../fields/color';
import { useOptionalOrbitForm } from '../form-context';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { readLayoutData } from '../lib/layout-data';
import { FieldRenderer } from '../screen-renderer';
import {
    CONTENT_WIDTH_LABELS,
    normalizeContentWidth,
    type ContentWidthOption,
    type LayoutMode,
} from '../theme/branding';
import { LAYOUT_MODE_LABELS, LayoutPreview } from '../theme/layout-previews';
import { TablePreviewSnippet } from '../theme/table-preview';
import { toPreviewColors } from '../theme/layout-themes';
import type { LayoutThemeDefinition, LayoutThemeToken, LayoutThemes } from '../theme/layout-themes';
import { Card, CardBody, CardHeader } from '../ui/card';

const LAYOUT_KEYS = [
    'layout.content_width',
] as const;

const TOKEN_DEFAULTS: Record<string, string> = {
    color_primary: '#17ce91',
    color_secondary: '#64748b',
    color_accent: '#fc8024',
    color_page_bg: '#f8fafc',
    color_panel_bg: '#ffffff',
    color_panel_border: '#e2e8f0',
    color_header_bg: '#ffffff',
    color_header_border: '#e2e8f0',
    color_nav_bg: '#ffffff',
    color_nav_border: '#e2e8f0',
    color_nav_muted: '#ecfdf5',
    color_nav_section_fg: '#334155',
    color_nav_group_fg: '#64748b',
    color_nav_active_bg: '#d1fae5',
    color_nav_active_fg: '#047857',
    color_rail_bg: '#ffffff',
    color_rail_border: '#e2e8f0',
    color_rail_symbol_bg: '#17ce91',
    color_rail_icon: '#64748b',
    color_rail_active_bg: '#d1fae5',
    color_rail_active_fg: '#047857',
};

type Tone = 'light' | 'dark';

function asFields(value: unknown): FieldNode[] {
    return Array.isArray(value) ? (value as FieldNode[]) : [];
}

function configKey(key: string): string {
    return key.replace(/\./g, '__');
}

function useConfigSlice(): Record<string, unknown> {
    const form = useOptionalOrbitForm();

    return (form?.getValue('config') as Record<string, unknown> | undefined) ?? {};
}

function findFieldByKey(fields: FieldNode[], dottedKey: string): FieldNode | undefined {
    const encoded = configKey(dottedKey);

    return fields.find((field) => field.name?.includes(encoded));
}

function fieldsByKeys(fields: FieldNode[], keys: readonly string[]): FieldNode[] {
    return keys.map((key) => findFieldByKey(fields, key)).filter((field): field is FieldNode => field !== undefined);
}

function readLayoutTokenColors(
    config: Record<string, unknown>,
    mode: LayoutMode,
    theme: LayoutThemeDefinition,
    tone: 'light' | 'dark' | 'single' = 'single',
): Record<string, string> {
    const colors: Record<string, string> = {};

    for (const token of theme.tokens) {
        const storageKey =
            theme.dualTone && tone !== 'single'
                ? configKey(`theme.${mode}.${tone}.${token.key}`)
                : configKey(`theme.${mode}.${token.key}`);

        colors[token.key] = String(config[storageKey] ?? TOKEN_DEFAULTS[token.key] ?? '#64748b');
    }

    return colors;
}

function groupTokens(theme: LayoutThemeDefinition | undefined): Array<[string, LayoutThemeToken[]]> {
    if (!theme) {
        return [];
    }

    const groups = new Map<string, LayoutThemeToken[]>();

    theme.tokens.forEach((token) => {
        const group = token.group ?? 'General';
        groups.set(group, [...(groups.get(group) ?? []), token]);
    });

    return Array.from(groups.entries());
}

function ToneToggle({
    value,
    onChange,
}: {
    value: Tone;
    onChange: (tone: Tone) => void;
}) {
    const t = useT();

    return (
        <div className="inline-flex rounded-full border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5">
            {([
                ['light', 'Light'],
                ['dark', 'Dark'],
            ] as const).map(([tone, label]) => {
                const active = value === tone;

                return (
                    <button
                        key={tone}
                        type="button"
                        onClick={() => onChange(tone)}
                        className={cn(
                            'rounded-full px-3 py-1 text-xs font-medium transition',
                            active
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                                : 'text-gray-500 dark:text-gray-400',
                        )}
                    >
                        {t(label)}
                    </button>
                );
            })}
        </div>
    );
}

function tokenSwatches(colors: Record<string, string>, tokens: LayoutThemeToken[]): string[] {
    return tokens.slice(0, 6).map((token) => colors[token.key]).filter(Boolean);
}

/** Admin design settings: layout mode, content width and per-layout theme colours. */
export function DesignSettingsLayout({ node }: LayoutComponentProps) {
    const t = useT();
    const layoutData = readLayoutData(node);
    const fields = asFields(layoutData.fields);
    const form = useOptionalOrbitForm();
    const config = useConfigSlice();
    const layoutModes = (layoutData.layoutModes as Record<LayoutMode, string> | undefined) ?? LAYOUT_MODE_LABELS;
    const layoutThemes = (layoutData.layoutThemes as LayoutThemes | undefined) ?? {};

    const layoutFields = fieldsByKeys(fields, LAYOUT_KEYS);

    const selectedMode = (config[configKey('layout.mode')] as LayoutMode | undefined) ?? 'palette-split';
    const contentWidth = normalizeContentWidth(
        (config[configKey('layout.content_width')] as ContentWidthOption | undefined) ?? 'default',
    );
    const activeTheme = layoutThemes[selectedMode];
    const layoutPalette = String(config[configKey(`theme.${selectedMode}.palette`)] ?? '');
    const selectedLayoutPalette =
        activeTheme && (layoutPalette === 'custom' || layoutPalette in activeTheme.presets) ? layoutPalette : 'custom';
    const [previewTone, setPreviewTone] = useState<Tone>('light');
    const layoutColors = activeTheme ? readLayoutTokenColors(config, selectedMode, activeTheme, 'light') : {};
    const layoutColorsDark =
        activeTheme?.dualTone ? readLayoutTokenColors(config, selectedMode, activeTheme, 'dark') : {};
    const activePreviewTone = activeTheme?.dualTone ? previewTone : 'light';
    const activeToneColors = activePreviewTone === 'dark' ? layoutColorsDark : layoutColors;
    const previewColors = toPreviewColors(activeToneColors);
    const tokenGroups = groupTokens(activeTheme);
    const contentWidthLabel = CONTENT_WIDTH_LABELS[contentWidth];

    const selectMode = (mode: LayoutMode) => {
        form?.setValue(`config[${configKey('layout.mode')}]`, mode);
    };

    const applyLayoutPreset = (mode: LayoutMode, presetKey: string) => {
        const theme = layoutThemes[mode];

        if (!form || !theme) {
            return;
        }

        form.setValue(`config[${configKey(`theme.${mode}.palette`)}]`, presetKey);

        if (presetKey === 'custom') {
            return;
        }

        const preset = theme.presets[presetKey];

        if (!preset) {
            return;
        }

        if (theme.dualTone && preset.light && preset.dark) {
            for (const [token, value] of Object.entries(preset.light)) {
                form.setValue(`config[${configKey(`theme.${mode}.light.${token}`)}]`, value);
            }

            for (const [token, value] of Object.entries(preset.dark)) {
                form.setValue(`config[${configKey(`theme.${mode}.dark.${token}`)}]`, value);
            }

            return;
        }

        if (!preset.colors) {
            return;
        }

        for (const [token, value] of Object.entries(preset.colors)) {
            form.setValue(`config[${configKey(`theme.${mode}.${token}`)}]`, value);
        }
    };

    const setLayoutColor = (
        mode: LayoutMode,
        token: string,
        value: unknown,
        tone: 'light' | 'dark' | 'single' = 'single',
    ) => {
        const theme = layoutThemes[mode];
        const path =
            theme?.dualTone && tone !== 'single'
                ? `theme.${mode}.${tone}.${token}`
                : `theme.${mode}.${token}`;

        form?.setValue(`config[${configKey(path)}]`, value);
        form?.setValue(`config[${configKey(`theme.${mode}.palette`)}]`, 'custom');
    };

    return (
        <div className="space-y-6">
            <section className="space-y-3">
                <div>
                    <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100">레이아웃 선택</h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        레이아웃은 가로 스크롤 카드로 빠르게 비교하고 전환할 수 있습니다.
                        {contentWidthLabel ? ` · ${contentWidthLabel}` : ''}
                    </p>
                </div>
                {layoutFields.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4 md:max-w-md">
                        {layoutFields.map((field, index) => (
                            <FieldRenderer key={field.name ?? `layout-${index}`} node={field} data={{}} />
                        ))}
                    </div>
                ) : null}
                <div className="-mx-1 overflow-x-auto px-1 pb-1">
                    <div className="flex min-w-max gap-3">
                        {(Object.keys(layoutModes) as LayoutMode[]).map((mode) => {
                            const selected = selectedMode === mode;
                            const theme = layoutThemes[mode];
                            const snippetColors = theme
                                ? toPreviewColors(
                                      readLayoutTokenColors(
                                          config,
                                          mode,
                                          theme,
                                          theme.dualTone ? previewTone : 'light',
                                      ),
                                  )
                                : previewColors;

                            return (
                                <button
                                    key={mode}
                                    type="button"
                                    onClick={() => selectMode(mode)}
                                    className={cn(
                                        'w-[172px] shrink-0 rounded-2xl border p-3 text-left transition',
                                        selected
                                            ? 'border-orbit-primary bg-orbit-primary/5 ring-2 ring-orbit-primary/20'
                                            : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20',
                                    )}
                                >
                                    <LayoutPreview mode={mode} colors={snippetColors} variant="snippet" />
                                    <span className="mt-2 block text-sm font-medium text-gray-800 dark:text-gray-100">
                                        {t(layoutModes[mode] ?? LAYOUT_MODE_LABELS[mode])}
                                    </span>
                                    <span className="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                        {theme?.tokens.length ?? 0} {t('tokens')}
                                        {theme?.dualTone ? ` · ${t('Dual tone')}` : ''}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            </section>

            {activeTheme ? (
                <section className="space-y-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100">레이아웃 색상 설정</h2>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {layoutModes[selectedMode] ?? selectedMode} 전용 토큰과 프리셋을 세밀하게 조정합니다.
                            </p>
                        </div>
                        {activeTheme.dualTone ? (
                            <ToneToggle value={previewTone} onChange={setPreviewTone} />
                        ) : null}
                    </div>

                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <Card className="xl:col-span-1 xl:sticky xl:top-6 xl:z-10 xl:self-start">
                            <CardHeader
                                title="레이아웃 미리보기"
                                description={`${t(layoutModes[selectedMode] ?? selectedMode)}의 ${t(activeTheme.dualTone && activePreviewTone === 'dark' ? 'Dark' : 'Light')} 톤입니다.`}
                            />
                            <CardBody className="space-y-4">
                                <LayoutPreview mode={selectedMode} colors={previewColors} variant="live" className="shadow-sm" />
                                <TablePreviewSnippet colors={previewColors} />
                                <div className="grid grid-cols-2 gap-3">
                                    {[
                                        ['Page', activeToneColors.color_page_bg],
                                        ['Header', activeToneColors.color_header_bg],
                                        ['Navigation', activeToneColors.color_nav_bg],
                                        ['Active', activeToneColors.color_nav_active_bg],
                                    ].map(([label, color]) => (
                                        <div
                                            key={label}
                                            className="rounded-xl border p-3"
                                            style={{ borderColor: activeToneColors.color_panel_border ?? '#e2e8f0' }}
                                        >
                                            <div
                                                className="mb-2 h-8 rounded-lg"
                                                style={{ backgroundColor: color ?? '#ffffff' }}
                                            />
                                            <p className="text-xs font-medium text-gray-600 dark:text-gray-300">{t(label)}</p>
                                        </div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>

                        <Card className="xl:col-span-2">
                            <CardHeader
                                title="컬러 폼"
                                description="프리셋을 고른 뒤 필요한 토큰만 커스텀하면 자동으로 Custom 상태로 전환됩니다."
                            />
                            <CardBody className="space-y-6">
                                <div className="space-y-3">
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {t('Presets')}
                                        </p>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
                                        {Object.entries(activeTheme.presets).map(([key, preset]) => {
                                            const selected = selectedLayoutPalette === key;
                                            const colors = previewTone === 'dark'
                                                ? Object.values(preset.dark ?? preset.colors ?? {})
                                                : Object.values(preset.light ?? preset.colors ?? {});

                                            return (
                                                <button
                                                    key={key}
                                                    type="button"
                                                    onClick={() => applyLayoutPreset(selectedMode, key)}
                                                    className={cn(
                                                        'rounded-xl border p-3 text-left transition',
                                                        selected
                                                            ? 'border-orbit-primary bg-orbit-primary/5 ring-2 ring-orbit-primary/20'
                                                            : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20',
                                                    )}
                                                >
                                                    <div className="mb-2 flex gap-1">
                                                        {colors.slice(0, 6).map((color, index) => (
                                                            <span
                                                                key={`${key}-${previewTone}-${index}`}
                                                                className="h-4 flex-1 rounded-md ring-1 ring-black/5"
                                                                style={{ backgroundColor: color }}
                                                            />
                                                        ))}
                                                    </div>
                                                    <span className="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                                        {t(preset.label)}
                                                    </span>
                                                </button>
                                            );
                                        })}

                                        <button
                                            type="button"
                                            onClick={() => applyLayoutPreset(selectedMode, 'custom')}
                                            className={cn(
                                                'rounded-xl border p-3 text-left transition',
                                                selectedLayoutPalette === 'custom'
                                                    ? 'border-orbit-primary bg-orbit-primary/5 ring-2 ring-orbit-primary/20'
                                                    : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20',
                                            )}
                                        >
                                            <div className="mb-2 flex gap-1">
                                                {tokenSwatches(activeToneColors, activeTheme.tokens).map((color, index) => (
                                                    <span
                                                        key={`custom-${index}`}
                                                        className="h-4 flex-1 rounded-md ring-1 ring-black/5"
                                                        style={{ backgroundColor: color }}
                                                    />
                                                ))}
                                            </div>
                                            <span className="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                                {t('Custom')}
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {tokenGroups.map(([group, tokens]) => (
                                        <div
                                            key={group}
                                            className="rounded-2xl border border-gray-200 p-4 dark:border-white/10"
                                        >
                                            <div className="mb-4 flex items-center justify-between gap-3">
                                                <div>
                                                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">{t(group)}</h3>
                                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {t(group)} 영역의 배경, 보더, 활성 상태를 세밀하게 조정합니다.
                                                    </p>
                                                </div>
                                                <div className="flex gap-1">
                                                    {tokens.slice(0, 4).map((token) => (
                                                        <span
                                                            key={`${group}-${token.key}`}
                                                            className="h-4 w-4 rounded-full ring-1 ring-black/5"
                                                            style={{ backgroundColor: activeToneColors[token.key] }}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                {tokens.map((token) => {
                                                    const fieldName = activeTheme.dualTone
                                                        ? `config[${configKey(`theme.${selectedMode}.${previewTone}.${token.key}`)}]`
                                                        : `config[${configKey(`theme.${selectedMode}.${token.key}`)}]`;
                                                    const value = activeToneColors[token.key];

                                                    return (
                                                        <ColorField
                                                            key={`${previewTone}-${token.key}`}
                                                            node={{
                                                                component: 'color',
                                                                name: fieldName,
                                                                value,
                                                                attributes: { title: t(token.label) },
                                                                errors: [],
                                                            }}
                                                            data={{}}
                                                            value={value}
                                                            name={fieldName}
                                                            attributes={{ title: t(token.label) }}
                                                            errors={[]}
                                                            onChange={(next) =>
                                                                setLayoutColor(
                                                                    selectedMode,
                                                                    token.key,
                                                                    next,
                                                                    activeTheme.dualTone ? previewTone : 'single',
                                                                )
                                                            }
                                                        />
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>
                    </div>
                </section>
            ) : null}
        </div>
    );
}
