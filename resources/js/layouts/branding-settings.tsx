import { useState } from 'react';
import type { FieldNode, LayoutComponentProps } from '../contract';
import { useOptionalOrbitForm } from '../form-context';
import { readLayoutData } from '../lib/layout-data';
import type { MediaItem } from '../media/types';
import { FieldRenderer } from '../screen-renderer';
import { Card, CardBody, CardHeader } from '../ui/card';

const GENERAL_KEYS = [
    'branding.name',
    'branding.theme_toggle_enabled',
    'branding.theme_mode',
] as const;

const LOGO_KEYS = [
    'branding.logo',
    'branding.logo_dark',
] as const;

const SYMBOL_KEYS = [
    'branding.symbol',
    'branding.symbol_dark',
    'branding.favicon',
] as const;

const COLOR_KEYS = [
    'branding.palette',
    'branding.color_primary',
    'branding.color_secondary',
    'branding.color_accent',
] as const;

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

function resolveMediaUrl(value: unknown): string | null {
    if (typeof value === 'string' && value.length > 0) {
        return value;
    }

    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const item = value as MediaItem;

        return item.url ?? item.thumbnail ?? item.relativeUrl ?? null;
    }

    if (Array.isArray(value) && value.length > 0) {
        const first = value[0];

        if (typeof first === 'object' && first !== null) {
            const item = first as MediaItem;

            return item.url ?? item.thumbnail ?? item.relativeUrl ?? null;
        }
    }

    return null;
}

/** Branding settings: admin identity, logos and brand colours. */
export function BrandingSettingsLayout({ node }: LayoutComponentProps) {
    const layoutData = readLayoutData(node);
    const fields = asFields(layoutData.fields);
    const config = useConfigSlice();

    const generalFields = fieldsByKeys(fields, GENERAL_KEYS);
    const logoFields = fieldsByKeys(fields, LOGO_KEYS);
    const symbolFields = fieldsByKeys(fields, SYMBOL_KEYS);
    const colorFields = fieldsByKeys(fields, COLOR_KEYS);

    const defaultThemeMode = String(config[configKey('branding.theme_mode')] ?? 'light');
    const initialPreviewTone: Tone = defaultThemeMode === 'dark' ? 'dark' : 'light';
    const [previewTone, setPreviewTone] = useState<Tone>(initialPreviewTone);

    const brandName = String(config[configKey('branding.name')] ?? 'Orbit');
    const primaryColor = String(config[configKey('branding.color_primary')] ?? '#17ce91');
    const secondaryColor = String(config[configKey('branding.color_secondary')] ?? '#64748b');
    const accentColor = String(config[configKey('branding.color_accent')] ?? '#fc8024');
    const logoUrl = resolveMediaUrl(config[configKey('branding.logo')] ?? findFieldByKey(fields, 'branding.logo')?.value);
    const logoUrlDark = resolveMediaUrl(
        config[configKey('branding.logo_dark')] ?? findFieldByKey(fields, 'branding.logo_dark')?.value,
    );
    const symbolUrl = resolveMediaUrl(
        config[configKey('branding.symbol')] ?? findFieldByKey(fields, 'branding.symbol')?.value,
    );
    const symbolUrlDark = resolveMediaUrl(
        config[configKey('branding.symbol_dark')] ?? findFieldByKey(fields, 'branding.symbol_dark')?.value,
    );
    const activeLogoUrl = previewTone === 'dark' ? logoUrlDark ?? logoUrl : logoUrl ?? logoUrlDark;
    const activeSymbolUrl = previewTone === 'dark' ? symbolUrlDark ?? symbolUrl : symbolUrl ?? symbolUrlDark;
    const previewBackground = previewTone === 'dark' ? '#0f172a' : '#ffffff';
    const previewBorder = previewTone === 'dark' ? '#1e293b' : '#e2e8f0';
    const previewMuted = previewTone === 'dark' ? '#334155' : '#ecfdf5';

    return (
        <div className="space-y-6">
            <section className="space-y-3">
                <div>
                    <h2 className="text-base font-semibold text-gray-900 dark:text-gray-100">브랜딩</h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        관리자 이름, 로고, 심볼, 파비콘과 브랜드 컬러를 설정합니다.
                    </p>
                </div>
                <div className="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <Card className="xl:col-span-1">
                        <CardHeader title="브랜드 미리보기" description="현재 로고와 브랜드 컬러가 반영됩니다." />
                        <CardBody className="space-y-4">
                            <div
                                className="rounded-2xl border p-4"
                                style={{
                                    backgroundColor: previewBackground,
                                    borderColor: previewBorder,
                                }}
                            >
                                <div className="flex items-center gap-3">
                                    {activeLogoUrl || activeSymbolUrl ? (
                                        <img
                                            src={activeLogoUrl ?? activeSymbolUrl ?? ''}
                                            alt=""
                                            className="h-12 max-w-[120px] object-contain"
                                        />
                                    ) : (
                                        <span
                                            className="flex h-12 w-12 items-center justify-center rounded-2xl text-base font-bold text-white"
                                            style={{ backgroundColor: primaryColor }}
                                        >
                                            {brandName.slice(0, 1).toUpperCase()}
                                        </span>
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {brandName}
                                        </p>
                                        <p className="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {previewTone === 'dark' ? 'Dark' : 'Light'}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    className="mt-4 rounded-xl border p-3"
                                    style={{
                                        backgroundColor: previewBackground,
                                        borderColor: previewBorder,
                                    }}
                                >
                                    <div className="mb-3 flex gap-2">
                                        {[primaryColor, secondaryColor, accentColor].map((color, index) => (
                                            <span
                                                key={`brand-swatch-${index}`}
                                                className="h-3 flex-1 rounded-full"
                                                style={{ backgroundColor: color }}
                                            />
                                        ))}
                                    </div>
                                    <div className="space-y-2">
                                        <div
                                            className="h-3 rounded-full"
                                            style={{ backgroundColor: primaryColor, width: '44%' }}
                                        />
                                        <div
                                            className="h-2 rounded-full"
                                            style={{ backgroundColor: previewMuted, width: '70%' }}
                                        />
                                        <div
                                            className="h-2 rounded-full"
                                            style={{ backgroundColor: previewMuted, width: '58%' }}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="inline-flex rounded-full border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5">
                                {([
                                    ['light', 'Light'],
                                    ['dark', 'Dark'],
                                ] as const).map(([tone, label]) => {
                                    const active = previewTone === tone;

                                    return (
                                        <button
                                            key={tone}
                                            type="button"
                                            onClick={() => setPreviewTone(tone)}
                                            className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                                                active
                                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                                                    : 'text-gray-500 dark:text-gray-400'
                                            }`}
                                        >
                                            {label}
                                        </button>
                                    );
                                })}
                            </div>
                        </CardBody>
                    </Card>

                    <Card className="xl:col-span-2">
                        <CardHeader
                            title="아이덴티티"
                            description="관리자 이름, 로고, 심볼, 파비콘과 테마 전환 동작을 설정합니다."
                        />
                        <CardBody className="space-y-4">
                            {generalFields.length > 0 || logoFields.length > 0 || symbolFields.length > 0 ? (
                                <div className="space-y-6">
                                    {generalFields.length > 0 ? (
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            {generalFields.map((field, index) => (
                                                <FieldRenderer key={field.name ?? `general-${index}`} node={field} data={{}} />
                                            ))}
                                        </div>
                                    ) : null}

                                    {logoFields.length > 0 ? (
                                        <div className="space-y-3">
                                            <div>
                                                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">로고</h3>
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    라이트/다크 로고를 한 줄에서 비교하며 배치합니다.
                                                </p>
                                            </div>
                                            <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                                {logoFields.map((field, index) => (
                                                    <FieldRenderer key={field.name ?? `logo-${index}`} node={field} data={{}} />
                                                ))}
                                            </div>
                                        </div>
                                    ) : null}

                                    {symbolFields.length > 0 ? (
                                        <div className="space-y-3">
                                            <div>
                                                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">심볼 · 파비콘</h3>
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    심벌과 파비콘은 더 작은 미리보기로 한 줄에 정리합니다.
                                                </p>
                                            </div>
                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                                {symbolFields.map((field, index) => (
                                                    <FieldRenderer key={field.name ?? `symbol-${index}`} node={field} data={{}} />
                                                ))}
                                            </div>
                                        </div>
                                    ) : null}

                                    {colorFields.length > 0 ? (
                                        <div className="space-y-3">
                                            <div>
                                                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">브랜드 컬러</h3>
                                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    프리셋을 선택하거나 Primary, Secondary, Accent 컬러를 직접 지정합니다.
                                                </p>
                                            </div>
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                {colorFields.map((field, index) => (
                                                    <FieldRenderer key={field.name ?? `color-${index}`} node={field} data={{}} />
                                                ))}
                                            </div>
                                        </div>
                                    ) : null}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    아이덴티티 필드를 불러오지 못했습니다. 페이지를 새로고침해 주세요.
                                </p>
                            )}
                        </CardBody>
                    </Card>
                </div>
            </section>
        </div>
    );
}
