import { useMemo, useState } from 'react';
import type { FieldNode, LayoutComponentProps } from '../contract';
import { AttachField } from '../fields/upload';
import { useOptionalOrbitForm } from '../form-context';
import { readLayoutData } from '../lib/layout-data';
import type { MediaItem } from '../media/types';
import { FieldRenderer } from '../screen-renderer';
import {
    GoogleSearchPreview,
    KakaoTalkPreview,
    OpenGraphPreview,
    type SeoPreviewData,
} from '../theme/seo-previews';
import { Card, CardBody, CardHeader } from '../ui/card';

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

function resolveThumbnailUrl(value: unknown): string | null {
    if (typeof value === 'string' && value.length > 0) {
        return value;
    }

    if (Array.isArray(value) && value.length > 0) {
        const first = value[0];

        if (typeof first === 'object' && first !== null) {
            const item = first as MediaItem;

            return item.thumbnail ?? item.url ?? null;
        }
    }

    return null;
}

function mediaItemsToUrl(items: MediaItem[]): string | null {
    const first = items[0];

    if (!first) {
        return null;
    }

    return first.thumbnail ?? first.url ?? null;
}

function usePreviewOrigin(): { previewUrl: string; previewHostname: string } {
    return useMemo(() => {
        if (typeof window === 'undefined') {
            return { previewUrl: 'https://example.com', previewHostname: 'example.com' };
        }

        const { origin, hostname } = window.location;

        return { previewUrl: origin, previewHostname: hostname };
    }, []);
}

/** SEO settings with live search and social share previews. */
export function SeoSettingsLayout({ node }: LayoutComponentProps) {
    const layoutData = readLayoutData(node);
    const fields = asFields(layoutData.fields);
    const form = useOptionalOrbitForm();
    const config = useConfigSlice();
    const { previewUrl, previewHostname } = usePreviewOrigin();

    const thumbnailField = fields.find((field) => field.name?.includes('seo__default_thumbnail'));
    const inputFields = fields.filter((field) => field !== thumbnailField);

    const [thumbnailUrl, setThumbnailUrl] = useState<string | null>(() =>
        resolveThumbnailUrl(thumbnailField?.value ?? config[configKey('seo.default_thumbnail')]),
    );

    const previewData: SeoPreviewData = useMemo(
        () => ({
            siteTitle: String(config[configKey('seo.site_title')] ?? ''),
            titleSeparator: String(config[configKey('seo.title_separator')] ?? '|'),
            siteDescription: String(config[configKey('seo.site_description')] ?? ''),
            snippet: String(config[configKey('seo.snippet')] ?? ''),
            thumbnailUrl,
            previewUrl,
            previewHostname,
            robots: String(config[configKey('seo.robots')] ?? 'index,follow'),
        }),
        [config, thumbnailUrl, previewUrl, previewHostname],
    );

    return (
        <div className="grid grid-cols-1 gap-6 xl:grid-cols-5">
            <div className="space-y-6 xl:col-span-2">
                <Card>
                    <CardHeader title="SEO 기본값" description="검색엔진과 SNS 공유에 사용되는 기본 메타 정보입니다." />
                    <CardBody className="space-y-4">
                        {inputFields.map((field, index) => (
                            <FieldRenderer key={field.name ?? index} node={field} data={{}} />
                        ))}

                        {thumbnailField ? (
                            <AttachField
                                node={thumbnailField}
                                data={{}}
                                value={form?.getValue(thumbnailField.name ?? '') ?? thumbnailField.value}
                                name={thumbnailField.name}
                                attributes={thumbnailField.attributes}
                                errors={thumbnailField.errors}
                                onChange={(value) => {
                                    form?.setValue(thumbnailField.name, value);
                                }}
                                onAssetsChange={(items) => {
                                    setThumbnailUrl(mediaItemsToUrl(items));
                                }}
                            />
                        ) : null}
                    </CardBody>
                </Card>
            </div>

            <div className="space-y-6 xl:col-span-3">
                <Card>
                    <CardHeader
                        title="미리보기"
                        description="입력값이 변경되면 검색·공유 스니펫이 실시간으로 반영됩니다."
                    />
                    <CardBody className="space-y-4">
                        <GoogleSearchPreview data={previewData} />
                        <KakaoTalkPreview data={previewData} />
                        <OpenGraphPreview data={previewData} />
                    </CardBody>
                </Card>
            </div>
        </div>
    );
}
