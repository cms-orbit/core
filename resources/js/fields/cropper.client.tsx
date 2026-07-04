import Cropper from 'cropperjs';
import { useEffect, useRef, useState } from 'react';
import 'cropperjs/dist/cropper.css';
import type { FieldComponentProps } from '../contract';
import { orbitFetch } from '../lib/http';
import { useT } from '../lib/i18n';
import { MediaLibraryDialog } from '../media/library';
import type { MediaItem } from '../media/types';
import { UiButton } from '../ui/button';
import { FieldShell } from '../ui/field-shell';
import { attr, bool, objectValue, str } from './shared';

async function uploadDataUrl(url: string, dataUrl: string, extra: Record<string, string>): Promise<MediaItem | null> {
    const blob = await (await fetch(dataUrl)).blob();
    const body = new FormData();
    body.append('files[]', blob, 'cropped.png');
    Object.entries(extra).forEach(([key, value]) => value && body.append(key, value));

    const response = await orbitFetch<{ data?: MediaItem | MediaItem[] }>(url, { method: 'POST', body });
    const data = response.data;

    if (Array.isArray(data)) {
        return data[0] ?? null;
    }

    return data ?? null;
}

/** Browser-only image cropper. */
export function CropperFieldClient(props: FieldComponentProps) {
    const { value, errors, onChange } = props;
    const t = useT();
    const target = attr<string>(props, 'target') ?? 'url';
    const uploadUrl = attr<string>(props, 'uploadUrl');
    const width = Number(attr(props, 'width') ?? 0);
    const height = Number(attr(props, 'height') ?? 0);
    const group = attr<string>(props, 'group');
    const path = attr<string>(props, 'path');
    const storage = attr<string>(props, 'storage');
    const purpose = attr<string>(props, 'purpose');
    const returnObjects = bool(attr(props, 'returnObjects'));

    const imageRef = useRef<HTMLImageElement>(null);
    const cropperRef = useRef<Cropper | null>(null);
    const fileInput = useRef<HTMLInputElement>(null);
    const [source, setSource] = useState<string | null>(null);
    const [libraryOpen, setLibraryOpen] = useState(false);
    const [preview, setPreview] = useState<string>(
        attr<string>(props, 'url') ??
            str(
                objectValue(value)?.thumbnail ??
                    objectValue(value)?.url ??
                    objectValue(value)?.relativeUrl ??
                    (target === 'url' ? value : ''),
            ),
    );
    const isLogo = purpose === 'logo';
    const previewFrameClass = isLogo ? 'h-28 w-full rounded-2xl' : 'h-20 w-20 rounded-2xl';

    const applyMediaItem = (item: MediaItem) => {
        setPreview(item.url);

        if (returnObjects) {
            onChange?.(item);
        } else {
            onChange?.(target === 'id' ? item.id : target === 'relativeUrl' ? item.relativeUrl ?? item.url : item.url);
        }
    };

    useEffect(() => {
        if (!source || !imageRef.current) {
            return;
        }

        const cropper = new Cropper(imageRef.current, {
            viewMode: 1,
            aspectRatio: width > 0 && height > 0 ? width / height : NaN,
            autoCropArea: 1,
        });
        cropperRef.current = cropper;

        return () => {
            cropper.destroy();
            cropperRef.current = null;
        };
    }, [source, width, height]);

    const pickFile = (files: FileList | null) => {
        const file = files?.[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => setSource(str(reader.result));
        reader.readAsDataURL(file);
    };

    const apply = () => {
        const cropper = cropperRef.current;

        if (!cropper) {
            return;
        }

        const canvas = cropper.getCroppedCanvas(width > 0 ? { width, height: height || width } : undefined);
        const dataUrl = canvas.toDataURL('image/png');
        setPreview(dataUrl);
        setSource(null);

        if (uploadUrl) {
            uploadDataUrl(uploadUrl, dataUrl, {
                group: group ?? '',
                path: path ?? '',
                storage: storage ?? '',
                purpose: purpose ?? '',
            }).then((item) => {
                if (!item) {
                    return;
                }

                setPreview(item.url);
                if (returnObjects) {
                    onChange?.(item);
                } else {
                    onChange?.(target === 'id' ? item.id : item.url);
                }
            });
        } else {
            onChange?.(dataUrl);
        }
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            {source ? (
                <div className="space-y-2">
                    <div
                        className="max-h-80 overflow-hidden rounded-md border"
                        style={{ borderColor: 'var(--color-orbit-panel-border, #cbd5e1)' }}
                    >
                        <img ref={imageRef} src={source} alt="Crop source" className="block max-w-full" />
                    </div>
                    <div className="flex gap-2">
                        <UiButton type="button" variant="primary" onClick={apply}>
                            {t('Crop & save')}
                        </UiButton>
                        <UiButton type="button" variant="default" onClick={() => setSource(null)}>
                            {t('Cancel')}
                        </UiButton>
                    </div>
                </div>
            ) : (
                <div className="space-y-3">
                    <label
                        className={`group relative flex cursor-pointer items-center justify-center overflow-hidden border border-dashed ${previewFrameClass}`}
                        style={{
                            backgroundColor: 'var(--color-orbit-nav-section-bg, #f8fafc)',
                            borderColor: 'var(--color-orbit-panel-border, #cbd5e1)',
                        }}
                    >
                        {preview ? (
                            <img src={preview} alt="" className="h-full w-full object-cover" />
                        ) : (
                            <div className="px-3 text-center">
                                <p
                                    className="text-sm font-medium"
                                    style={{ color: 'var(--color-orbit-secondary, #334155)' }}
                                >
                                    {t('Click to upload')}
                                </p>
                                <p
                                    className="mt-1 text-xs"
                                    style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}
                                >
                                    {t('or choose from media library')}
                                </p>
                            </div>
                        )}
                        {preview ? (
                            <div className="absolute inset-x-0 bottom-0 bg-black/45 px-3 py-2 text-[11px] font-medium text-white opacity-0 transition group-hover:opacity-100">
                                {t('Replace image')}
                            </div>
                        ) : null}
                        <input
                            ref={fileInput}
                            type="file"
                            accept={attr<string>(props, 'acceptedFiles') ?? 'image/*'}
                            className="hidden"
                            onChange={(event) => {
                                pickFile(event.target.files);
                                event.target.value = '';
                            }}
                        />
                    </label>
                    <div className="flex flex-wrap gap-2">
                        <UiButton type="button" variant="default" onClick={() => fileInput.current?.click()}>
                            {preview ? t('Upload') : t('Choose image')}
                        </UiButton>
                        <UiButton type="button" variant="default" onClick={() => setLibraryOpen(true)}>
                            {t('Media library')}
                        </UiButton>
                        {preview ? (
                            <UiButton type="button" variant="link" onClick={() => {
                                setPreview('');
                                onChange?.(null);
                            }}>
                                {t('Remove')}
                            </UiButton>
                        ) : null}
                    </div>
                </div>
            )}
            <MediaLibraryDialog
                open={libraryOpen}
                onClose={() => setLibraryOpen(false)}
                onSelect={(items) => items[0] && applyMediaItem(items[0])}
                accept="image"
                group={group}
            />
        </FieldShell>
    );
}
