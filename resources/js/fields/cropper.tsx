import Cropper from 'cropperjs';
import { useEffect, useRef, useState } from 'react';
import 'cropperjs/dist/cropper.css';
import type { FieldComponentProps } from '../contract';
import { orbitFetch } from '../lib/http';
import type { MediaItem } from '../media/types';
import { UiButton } from '../ui/button';
import { FieldShell } from '../ui/field-shell';
import { attr, str } from './shared';

async function uploadDataUrl(url: string, dataUrl: string): Promise<MediaItem | null> {
    const blob = await (await fetch(dataUrl)).blob();
    const body = new FormData();
    body.append('files[]', blob, 'cropped.png');

    const response = await orbitFetch<{ data?: MediaItem | MediaItem[] }>(url, { method: 'POST', body });
    const data = response.data;

    if (Array.isArray(data)) {
        return data[0] ?? null;
    }

    return data ?? null;
}

/** Image cropper backed by Cropper.js (replaces Orchid's cropper field). */
export function CropperField(props: FieldComponentProps) {
    const { value, errors, onChange } = props;
    const target = attr<string>(props, 'target') ?? 'url';
    const uploadUrl = attr<string>(props, 'uploadUrl');
    const width = Number(attr(props, 'width') ?? 0);
    const height = Number(attr(props, 'height') ?? 0);

    const imageRef = useRef<HTMLImageElement>(null);
    const cropperRef = useRef<Cropper | null>(null);
    const [source, setSource] = useState<string | null>(null);
    const [preview, setPreview] = useState<string>(attr<string>(props, 'url') ?? (target === 'url' ? str(value) : ''));

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
            uploadDataUrl(uploadUrl, dataUrl).then((item) => {
                if (!item) {
                    return;
                }

                setPreview(item.url);
                onChange?.(target === 'id' ? item.id : item.url);
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
                    <div className="max-h-80 overflow-hidden rounded-md border border-gray-300 dark:border-gray-700">
                        <img ref={imageRef} src={source} alt="Crop source" className="block max-w-full" />
                    </div>
                    <div className="flex gap-2">
                        <UiButton type="button" variant="primary" onClick={apply}>
                            Crop &amp; save
                        </UiButton>
                        <UiButton type="button" variant="default" onClick={() => setSource(null)}>
                            Cancel
                        </UiButton>
                    </div>
                </div>
            ) : (
                <div className="flex items-start gap-3">
                    <div className="flex h-28 w-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                        {preview ? (
                            <img src={preview} alt="" className="h-full w-full object-cover" />
                        ) : (
                            <span className="text-xs text-gray-400">No image</span>
                        )}
                    </div>
                    <label className="cursor-pointer">
                        <span className="inline-flex items-center rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                            Choose image
                        </span>
                        <input
                            type="file"
                            accept={attr<string>(props, 'acceptedFiles') ?? 'image/*'}
                            className="hidden"
                            onChange={(event) => {
                                pickFile(event.target.files);
                                event.target.value = '';
                            }}
                        />
                    </label>
                </div>
            )}
        </FieldShell>
    );
}
