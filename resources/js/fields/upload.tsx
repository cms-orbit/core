import { useRef, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { orbitFetch } from '../lib/http';
import { MediaLibraryDialog } from '../media/library';
import type { MediaItem } from '../media/types';
import { formatBytes, inferMediaType } from '../media/types';
import { UiButton } from '../ui/button';
import { FieldShell } from '../ui/field-shell';
import { attr, bool, str } from './shared';

function asAssets(value: unknown): MediaItem[] {
    if (Array.isArray(value)) {
        return value.filter((item): item is MediaItem => Boolean(item) && typeof item === 'object');
    }

    return [];
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
    const { errors, onChange } = props;
    const multiple = bool(attr(props, 'multiple'));
    const uploadUrl = attr<string>(props, 'uploadUrl');
    const group = attr<string>(props, 'group');
    const path = attr<string>(props, 'path');
    const storage = attr<string>(props, 'storage');

    const [assets, setAssets] = useState<MediaItem[]>(() => asAssets(props.value));
    const [libraryOpen, setLibraryOpen] = useState(false);
    const [uploading, setUploading] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const commit = (next: MediaItem[]) => {
        setAssets(next);
        onChange?.(next.map((item) => item.id));
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
        uploadTo(uploadUrl, files, { group: group ?? '', path: path ?? '', storage: storage ?? '' })
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
                            className="flex items-center gap-2 rounded-md border border-gray-200 px-2 py-1.5 text-sm dark:border-gray-700"
                        >
                            {inferMediaType(item) === 'image' ? (
                                <img src={item.thumbnail ?? item.url} alt="" className="h-8 w-8 rounded object-cover" />
                            ) : (
                                <span className="flex h-8 w-8 items-center justify-center rounded bg-gray-100 text-[10px] text-gray-400 dark:bg-gray-700">
                                    {(item.extension ?? 'file').slice(0, 4)}
                                </span>
                            )}
                            <span className="min-w-0 flex-1 truncate">{item.original_name ?? item.name}</span>
                            <span className="text-xs text-gray-400">{formatBytes(item.size)}</span>
                            <button
                                type="button"
                                onClick={() => commit(assets.filter((a) => a.id !== item.id))}
                                className="text-gray-400 hover:text-red-600"
                                aria-label="Remove"
                            >
                                &times;
                            </button>
                        </li>
                    ))}
                </ul>
            ) : null}

            <div className="flex gap-2">
                <UiButton type="button" variant="default" onClick={() => fileInput.current?.click()} disabled={uploading}>
                    {uploading ? 'Uploading…' : (attr<string>(props, 'placeholder') ?? 'Upload file')}
                </UiButton>
                <UiButton type="button" variant="default" onClick={() => setLibraryOpen(true)}>
                    Media library
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
    const target = attr<string>(props, 'target') ?? 'url';
    const uploadUrl = attr<string>(props, 'uploadUrl');

    const initialUrl = attr<string>(props, 'url') ?? (target === 'url' ? str(props.value) : '');
    const [previewUrl, setPreviewUrl] = useState<string>(initialUrl);
    const [libraryOpen, setLibraryOpen] = useState(false);
    const fileInput = useRef<HTMLInputElement>(null);

    const apply = (item: MediaItem) => {
        setPreviewUrl(item.url);

        if (target === 'id') {
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

        uploadTo(uploadUrl, files, {}).then((items) => {
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
                <div className="flex h-28 w-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                    {previewUrl ? (
                        <img src={previewUrl} alt="" className="h-full w-full object-cover" />
                    ) : (
                        <span className="text-xs text-gray-400">No image</span>
                    )}
                </div>
                <div className="flex flex-col gap-2">
                    <UiButton type="button" variant="default" onClick={() => fileInput.current?.click()}>
                        Upload
                    </UiButton>
                    <UiButton type="button" variant="default" onClick={() => setLibraryOpen(true)}>
                        Media library
                    </UiButton>
                    {previewUrl ? (
                        <UiButton type="button" variant="link" onClick={clear}>
                            Remove
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
