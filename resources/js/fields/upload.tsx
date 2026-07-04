import { useRef, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { orbitFetch } from '../lib/http';
import { MediaLibraryDialog } from '../media/library';
import type { MediaItem } from '../media/types';
import { formatBytes, inferMediaType } from '../media/types';
import { useT } from '../lib/i18n';
import { UiButton } from '../ui/button';
import { FieldShell } from '../ui/field-shell';
import { attr, bool, objectValue, str } from './shared';

function asAssets(value: unknown): MediaItem[] {
    if (Array.isArray(value)) {
        return value.filter((item): item is MediaItem => Boolean(item) && typeof item === 'object');
    }

    if (value && typeof value === 'object') {
        return [value as MediaItem];
    }

    return [];
}

function mediaUrl(value: unknown): string {
    const object = objectValue(value);

    return str(object?.thumbnail ?? object?.url ?? object?.relativeUrl ?? value);
}

async function uploadTo(url: string, files: FileList | File[], extra: Record<string, string>): Promise<MediaItem[]> {
    const body = new FormData();
    Array.from(files).forEach((file) => body.append('files[]', file));
    Object.entries(extra).forEach(([key, value]) => value && body.append(key, value));

    const response = await orbitFetch<{ data?: MediaItem | MediaItem[] } | MediaItem[]>(url, {
        method: 'POST',
        body,
    });

    if (Array.isArray(response)) {
        return response;
    }

    const data = response.data;

    return Array.isArray(data) ? data : data ? [data] : [];
}

export function AttachField(props: FieldComponentProps) {
    const { errors, onChange, onAssetsChange } = props;
    const t = useT();
    const multiple = bool(attr(props, 'multiple'));
    const uploadUrl = attr<string>(props, 'uploadUrl');
    const group = attr<string>(props, 'group');
    const path = attr<string>(props, 'path');
    const storage = attr<string>(props, 'storage');
    const purpose = attr<string>(props, 'purpose');
    const returnObjects = bool(attr(props, 'returnObjects'));

    const [assets, setAssets] = useState<MediaItem[]>(() => asAssets(props.value));
    const [libraryOpen, setLibraryOpen] = useState(false);
    const [uploading, setUploading] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const commit = (next: MediaItem[]) => {
        setAssets(next);
        onChange?.(returnObjects ? (multiple ? next : next[0] ?? null) : next.map((item) => item.id));
        onAssetsChange?.(next);
    };

    const addAssets = (incoming: MediaItem[]) => {
        const existing = new Set(assets.map((item) => String(item.id)));
        const merged = multiple
            ? [...assets, ...incoming.filter((item) => !existing.has(String(item.id)))]
            : incoming.slice(0, 1);
        commit(merged);
    };

    const handleUpload = (files: FileList | null) => {
        if (!files || files.length === 0 || !uploadUrl) {
            return;
        }

        setUploading(true);
        uploadTo(uploadUrl, files, { group: group ?? '', path: path ?? '', storage: storage ?? '', purpose: purpose ?? '' })
            .then(addAssets)
            .finally(() => setUploading(false));
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            {assets.length > 0 ? (
                <ul className="mb-2 space-y-1">
                    {assets.map((item) => (
                        <li
                            key={item.id}
                            className="flex items-center gap-2 rounded-xl border px-2.5 py-2 text-sm"
                            style={{
                                backgroundColor: 'var(--color-orbit-panel-bg, #ffffff)',
                                borderColor: 'var(--color-orbit-panel-border, #e2e8f0)',
                            }}
                        >
                            {inferMediaType(item) === 'image' ? (
                                <img src={item.thumbnail ?? item.url} alt="" className="h-8 w-8 rounded object-cover" />
                            ) : (
                                <span
                                    className="flex h-8 w-8 items-center justify-center rounded text-[10px]"
                                    style={{
                                        backgroundColor: 'var(--color-orbit-nav-section-bg, #f1f5f9)',
                                        color: 'var(--color-orbit-nav-group-fg, #64748b)',
                                    }}
                                >
                                    {(item.extension ?? 'file').slice(0, 4)}
                                </span>
                            )}
                            <span className="min-w-0 flex-1 truncate" style={{ color: 'var(--color-orbit-secondary, #334155)' }}>
                                {item.original_name ?? item.name}
                            </span>
                            <span className="text-xs" style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}>
                                {formatBytes(item.size)}
                            </span>
                            <button
                                type="button"
                                onClick={() => commit(assets.filter((a) => a.id !== item.id))}
                                className="hover:text-red-600"
                                style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}
                                aria-label="Remove"
                            >
                                &times;
                            </button>
                        </li>
                    ))}
                </ul>
            ) : null}

            <div
                className="mb-2 rounded-xl border border-dashed px-4 py-4"
                style={{
                    backgroundColor: 'var(--color-orbit-nav-section-bg, #f8fafc)',
                    borderColor: 'var(--color-orbit-panel-border, #cbd5e1)',
                }}
            >
                <p className="text-sm font-medium" style={{ color: 'var(--color-orbit-secondary, #334155)' }}>
                    {attr<string>(props, 'placeholder') ?? t('Upload file')}
                </p>
                <p className="mt-1 text-xs" style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}>
                    {multiple ? '여러 파일을 추가하거나 미디어 라이브러리에서 선택할 수 있습니다.' : '파일을 업로드하거나 미디어 라이브러리에서 선택할 수 있습니다.'}
                </p>
            </div>

            <div className="flex gap-2">
                <UiButton type="button" variant="default" onClick={() => fileInput.current?.click()} disabled={uploading}>
                    {uploading ? t('Uploading…') : t('Upload')}
                </UiButton>
                <UiButton type="button" variant="default" onClick={() => setLibraryOpen(true)}>
                    {t('Media library')}
                </UiButton>
                <input
                    ref={fileInput}
                    type="file"
                    multiple={multiple}
                    accept={attr<string>(props, 'accept')}
                    className="hidden"
                    onChange={(event) => {
                        handleUpload(event.target.files);
                        event.target.value = '';
                    }}
                />
            </div>

            <MediaLibraryDialog
                open={libraryOpen}
                onClose={() => setLibraryOpen(false)}
                onSelect={addAssets}
                multiple={multiple}
                group={group}
            />
        </FieldShell>
    );
}

export function PictureField(props: FieldComponentProps) {
    const { errors, onChange } = props;
    const t = useT();
    const target = attr<string>(props, 'target') ?? 'url';
    const uploadUrl = attr<string>(props, 'uploadUrl');
    const group = attr<string>(props, 'group');
    const path = attr<string>(props, 'path');
    const storage = attr<string>(props, 'storage');
    const purpose = attr<string>(props, 'purpose');
    const returnObjects = bool(attr(props, 'returnObjects'));

    const initialUrl = attr<string>(props, 'url') ?? mediaUrl(props.value);
    const [previewUrl, setPreviewUrl] = useState<string>(initialUrl);
    const [libraryOpen, setLibraryOpen] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const apply = (item: MediaItem) => {
        setPreviewUrl(item.url);

        if (returnObjects) {
            onChange?.(item);
        } else if (target === 'id') {
            onChange?.(item.id);
        } else if (target === 'relativeUrl') {
            onChange?.(item.relativeUrl ?? item.url);
        } else {
            onChange?.(item.url);
        }
    };

    const clear = () => {
        setPreviewUrl('');
        onChange?.(null);
    };

    const handleUpload = (files: FileList | null) => {
        if (!files || files.length === 0 || !uploadUrl) {
            return;
        }

        uploadTo(uploadUrl, files, {
            group: group ?? '',
            path: path ?? '',
            storage: storage ?? '',
            purpose: purpose ?? '',
        }).then((items) => {
            if (items[0]) {
                apply(items[0]);
            }
        });
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="flex items-start gap-3">
                <div
                    className="flex h-28 w-28 items-center justify-center overflow-hidden rounded-lg border border-dashed"
                    style={{
                        backgroundColor: 'var(--color-orbit-nav-section-bg, #f8fafc)',
                        borderColor: 'var(--color-orbit-panel-border, #cbd5e1)',
                    }}
                >
                    {previewUrl ? (
                        <img src={previewUrl} alt="" className="h-full w-full object-cover" />
                    ) : (
                        <span className="text-xs" style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}>{t('No image')}</span>
                    )}
                </div>
                <div className="flex flex-col gap-2">
                    <UiButton type="button" variant="default" onClick={() => fileInput.current?.click()}>
                        {t('Upload')}
                    </UiButton>
                    <UiButton type="button" variant="default" onClick={() => setLibraryOpen(true)}>
                        {t('Media library')}
                    </UiButton>
                    {previewUrl ? (
                        <UiButton type="button" variant="link" onClick={clear}>
                            {t('Remove')}
                        </UiButton>
                    ) : null}
                    <input
                        ref={fileInput}
                        type="file"
                        accept={attr<string>(props, 'acceptedFiles') ?? 'image/*'}
                        className="hidden"
                        onChange={(event) => {
                            handleUpload(event.target.files);
                            event.target.value = '';
                        }}
                    />
                </div>
            </div>

            <MediaLibraryDialog
                open={libraryOpen}
                onClose={() => setLibraryOpen(false)}
                onSelect={(items) => items[0] && apply(items[0])}
                accept="image"
            />
        </FieldShell>
    );
}
