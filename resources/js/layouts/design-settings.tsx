import { useMemo } from 'react';
import type { FieldNode, LayoutComponentProps } from '../contract';
import { ColorField } from '../fields/color';
import { useOptionalOrbitForm } from '../form-context';
import { readLayoutData } from '../lib/layout-data';
import { cn } from '../lib/cn';
import type { MediaItem } from '../media/types';
import { FieldRenderer } from '../screen-renderer';
import type { LayoutMode } from '../theme/branding';
import {
    type LayoutThemeDefinition,
    type LayoutThemes,
    toPreviewColors,
} from '../theme/layout-themes';
import { LAYOUT_MODE_LABELS, LayoutPreview } from '../theme/layout-previews';
import { Card, CardBody, CardHeader } from '../ui/card';

const IDENTITY_KEYS = [
    'branding.name',
    'branding.logo',
    'branding.symbol',
    'branding.favicon',
    'branding.dark_mode',
] as const;

const TOKEN_DEFAULTS: Record<string, string> = {
    color_primary: '#17ce91',
    color_secondary: '#64748b',
    color_accent: '#fc8024',
    color_surface: '#f8fafc',
    color_muted: '#94a3b8',
};

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

function resolveMediaUrl(value: unknown): string | null {
    if (typeof value === 'string' && value.length > 0) {
        return value;
    }

    if (Array.isArray(value) && value.length > 0) {
        const first = value[0];

        if (typeof first === 'object' && first !== null) {
            const item = first as MediaItem;

            return item.url ?? item.thumbnail ?? null;
        }
    }

    return null;
}

/** Unified admin design settings: identity, layout theme presets and live preview. */
export function DesignSettingsLayout({ node }: LayoutComponentProps) {
    const layoutData = readLayoutData(node);
    const fields = asFields(layoutData.fields);
    const form = useOptionalOrbitForm();
    const config = useConfigSlice();
    const layoutModes = (layoutData.layoutModes as Record<LayoutMode, string> | undefined) ?? LAYOUT_MODE_LABELS;
    const layoutThemes = (layoutData.layoutThemes as LayoutThemes | undefined) ?? {};

    const identityFields = IDENTITY_KEYS.map((key) => findFieldByKey(fields, key)).filter(
        (field): field is FieldNode => field !== undefined,
    );

    const selectedMode = (config[configKey('layout.mode')] as LayoutMode | undefined) ?? 'sidebar-split';
    const activeTheme = layoutThemes[selectedMode];
    const layoutPalette = String(config[configKey(`theme.${selectedMode}.palette`)] ?? '');
    const layoutColors = useMemo(
        () => (activeTheme ? readLayoutTokenColors(config, selectedMode, activeTheme, 'light') : {}),
        [activeTheme, config, selectedMode],
    );
    const layoutColorsDark = useMemo(
        () => (activeTheme?.dualTone ? readLayoutTokenColors(config, selectedMode, activeTheme, 'dark') : {}),
        [activeTheme, config, selectedMode],
    );
    const previewColors = useMemo(() => toPreviewColors(layoutColors), [layoutColors]);

    const brandName = String(config[configKey('branding.name')] ?? 'Orbit');
    const logoUrl = resolveMediaUrl(config[configKey('branding.logo')] ?? findFieldByKey(fields, 'branding.logo')?.value);
    const symbolUrl = resolveMediaUrl(
        config[configKey('branding.symbol')] ?? findFieldByKey(fields, 'branding.symbol')?.value,
    );

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
        <div className="grid grid-cols-1 gap-6 xl:grid-cols-5">
            <div className="space-y-6 xl:col-span-2">
                <Card>
                    <CardHeader
                        title="아이덴티티"
                        description="관리자 페이지 이름, 로고, 파비콘 등 공통 브랜딩 정보입니다."
                    />
                    <CardBody className="space-y-4">
                        {identityFields.length > 0 ? (
                            identityFields.map((field, index) => (
                                <FieldRenderer key={field.name ?? index} node={field} data={{}} />
                            ))
                        ) : (
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                아이덴티티 필드를 불러오지 못했습니다. 페이지를 새로고침해 주세요.
                            </p>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader
                        title="레이아웃"
                        description="관리자 셸 레이아웃을 선택한 뒤, 해당 레이아웃 전용 색상 프리셋을 적용합니다."
                    />
                    <CardBody className="space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                            {(Object.keys(layoutModes) as LayoutMode[]).map((mode) => {
                                const selected = selectedMode === mode;
                                const theme = layoutThemes[mode];
                                const snippetColors = theme
                                    ? toPreviewColors(readLayoutTokenColors(config, mode, theme, 'light'))
                                    : previewColors;

                                return (
                                    <button
                                        key={mode}
                                        type="button"
                                        onClick={() => selectMode(mode)}
                                        className={cn(
                                            'rounded-xl border p-2 text-left transition',
                                            selected
                                                ? 'border-orbit-primary bg-orbit-primary/5 ring-2 ring-orbit-primary/20'
                                                : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20',
                                        )}
                                    >
                                        <LayoutPreview mode={mode} colors={snippetColors} variant="snippet" />
                                        <span className="mt-2 block px-1 text-xs font-medium text-gray-700 dark:text-gray-200">
                                            {layoutModes[mode] ?? LAYOUT_MODE_LABELS[mode]}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>

                        {activeTheme ? (
                            <div className="space-y-4 border-t border-gray-100 pt-4 dark:border-white/10">
                                <div>
                                    <p className="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {layoutModes[selectedMode] ?? selectedMode} 색상 프리셋
                                    </p>
                                    <p className="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        레이아웃마다 필요한 색상 수가 다릅니다. 선택한 레이아웃에 맞는 프리셋만
                                        표시됩니다.
                                    </p>
                                </div>

                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    {Object.entries(activeTheme.presets).map(([key, preset]) => {
                                        const selected = layoutPalette === key;
                                        const lightSwatches = Object.values(preset.light ?? preset.colors ?? {});
                                        const darkSwatches = Object.values(preset.dark ?? {});

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
                                                <div className="mb-1 flex gap-1">
                                                    {lightSwatches.map((color, index) => (
                                                        <span
                                                            key={`${key}-light-${index}`}
                                                            className="h-4 flex-1 rounded-md ring-1 ring-black/5"
                                                            style={{ backgroundColor: color }}
                                                            title="Light"
                                                        />
                                                    ))}
                                                </div>
                                                {darkSwatches.length > 0 ? (
                                                    <div className="mb-2 flex gap-1">
                                                        {darkSwatches.map((color, index) => (
                                                            <span
                                                                key={`${key}-dark-${index}`}
                                                                className="h-4 flex-1 rounded-md ring-1 ring-black/5"
                                                                style={{ backgroundColor: color }}
                                                                title="Dark"
                                                            />
                                                        ))}
                                                    </div>
                                                ) : null}
                                                <span className="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                                    {preset.label}
                                                </span>
                                            </button>
                                        );
                                    })}
                                    <button
                                        type="button"
                                        onClick={() => applyLayoutPreset(selectedMode, 'custom')}
                                        className={cn(
                                            'rounded-xl border p-3 text-left transition',
                                            layoutPalette === 'custom'
                                                ? 'border-orbit-primary bg-orbit-primary/5 ring-2 ring-orbit-primary/20'
                                                : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20',
                                        )}
                                    >
                                        <div className="mb-2 flex gap-1">
                                            {activeTheme.tokens.map((token) => (
                                                <span
                                                    key={token.key}
                                                    className="h-5 flex-1 rounded-md ring-1 ring-black/5"
                                                    style={{ backgroundColor: layoutColors[token.key] }}
                                                />
                                            ))}
                                        </div>
                                        <span className="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                            Custom
                                        </span>
                                    </button>
                                </div>

                                <div
                                    className={cn(
                                        'grid gap-4',
                                        activeTheme.tokens.length <= 2
                                            ? 'grid-cols-1 sm:grid-cols-2'
                                            : activeTheme.tokens.length >= 5
                                              ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'
                                              : 'grid-cols-1 sm:grid-cols-3',
                                    )}
                                >
                                    {activeTheme.dualTone ? (
                                        <>
                                            <p className="col-span-full text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Light
                                            </p>
                                            {activeTheme.tokens.map((token) => {
                                                const fieldName = `config[${configKey(`theme.${selectedMode}.light.${token.key}`)}]`;
                                                const value = layoutColors[token.key];

                                                return (
                                                    <ColorField
                                                        key={`light-${token.key}`}
                                                        node={{
                                                            component: 'color',
                                                            name: fieldName,
                                                            value,
                                                            attributes: { title: token.label },
                                                            errors: [],
                                                        }}
                                                        data={{}}
                                                        value={value}
                                                        name={fieldName}
                                                        attributes={{ title: token.label }}
                                                        errors={[]}
                                                        onChange={(next) =>
                                                            setLayoutColor(selectedMode, token.key, next, 'light')
                                                        }
                                                    />
                                                );
                                            })}
                                            <p className="col-span-full text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Dark
                                            </p>
                                            {activeTheme.tokens.map((token) => {
                                                const fieldName = `config[${configKey(`theme.${selectedMode}.dark.${token.key}`)}]`;
                                                const value = layoutColorsDark[token.key];

                                                return (
                                                    <ColorField
                                                        key={`dark-${token.key}`}
                                                        node={{
                                                            component: 'color',
                                                            name: fieldName,
                                                            value,
                                                            attributes: { title: token.label },
                                                            errors: [],
                                                        }}
                                                        data={{}}
                                                        value={value}
                                                        name={fieldName}
                                                        attributes={{ title: token.label }}
                                                        errors={[]}
                                                        onChange={(next) =>
                                                            setLayoutColor(selectedMode, token.key, next, 'dark')
                                                        }
                                                    />
                                                );
                                            })}
                                        </>
                                    ) : (
                                        activeTheme.tokens.map((token) => {
                                            const fieldName = `config[${configKey(`theme.${selectedMode}.${token.key}`)}]`;
                                            const value = layoutColors[token.key];

                                            return (
                                                <ColorField
                                                    key={token.key}
                                                    node={{
                                                        component: 'color',
                                                        name: fieldName,
                                                        value,
                                                        attributes: { title: token.label },
                                                        errors: [],
                                                    }}
                                                    data={{}}
                                                    value={value}
                                                    name={fieldName}
                                                    attributes={{ title: token.label }}
                                                    errors={[]}
                                                    onChange={(next) => setLayoutColor(selectedMode, token.key, next)}
                                                />
                                            );
                                        })
                                    )}
                                </div>
                            </div>
                        ) : null}
                    </CardBody>
                </Card>
            </div>

            <div className="space-y-6 xl:col-span-3">
                <Card>
                    <CardHeader
                        title="레이아웃 미리보기"
                        description={`${layoutModes[selectedMode] ?? selectedMode} 레이아웃의 실시간 미리보기입니다.`}
                    />
                    <CardBody>
                        <LayoutPreview mode={selectedMode} colors={previewColors} variant="live" className="shadow-sm" />
                        {activeTheme?.dualTone ? (
                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <p className="mb-2 text-xs font-medium text-gray-500">Light preview</p>
                                    <div className="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                        <div className="flex gap-1">
                                            {activeTheme.tokens.map((token) => (
                                                <span
                                                    key={`preview-light-${token.key}`}
                                                    className="h-6 flex-1 rounded-md"
                                                    style={{ backgroundColor: layoutColors[token.key] }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p className="mb-2 text-xs font-medium text-gray-500">Dark preview</p>
                                    <div className="rounded-lg border border-gray-700 bg-gray-900 p-3">
                                        <div className="flex gap-1">
                                            {activeTheme.tokens.map((token) => (
                                                <span
                                                    key={`preview-dark-${token.key}`}
                                                    className="h-6 flex-1 rounded-md"
                                                    style={{ backgroundColor: layoutColorsDark[token.key] }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : null}
                        <div className="mt-4 flex flex-wrap gap-3">
                            {activeTheme?.tokens.map((token) => (
                                <div
                                    key={token.key}
                                    className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    <span
                                        className="h-4 w-4 rounded-full ring-1 ring-black/10"
                                        style={{ backgroundColor: layoutColors[token.key] }}
                                    />
                                    {token.label}
                                </div>
                            ))}
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="브랜드 미리보기" description="아이덴티티 설정이 반영된 모습입니다." />
                    <CardBody>
                        <div className="flex items-center gap-4">
                            {logoUrl || symbolUrl ? (
                                <img
                                    src={logoUrl ?? symbolUrl ?? ''}
                                    alt=""
                                    className="h-14 max-w-[120px] object-contain"
                                />
                            ) : (
                                <span
                                    className="flex h-14 w-14 items-center justify-center rounded-2xl text-lg font-bold text-white shadow-lg"
                                    style={{ backgroundColor: previewColors.primary }}
                                >
                                    {brandName.slice(0, 1).toUpperCase()}
                                </span>
                            )}
                            <div className="min-w-0 flex-1 space-y-2">
                                <p className="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {brandName}
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {activeTheme?.tokens.map((token) => (
                                        <span
                                            key={token.key}
                                            className="rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                                            style={{ backgroundColor: layoutColors[token.key] }}
                                        >
                                            {token.label}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}
