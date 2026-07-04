import { useCallback, useEffect, useRef, useState } from 'react';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { UiButton } from '../ui/button';
import { Overlay } from '../ui/overlay';
import { deleteMedia, listMedia, uploadMedia } from './api';
import type { MediaEndpoints, MediaItem, MediaType } from './types';
import { formatBytes, inferMediaType } from './types';
import { useMediaEndpoints } from './use-media-endpoints';

const TYPE_TABS: { value: MediaType | 'all'; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'image', label: 'Images' },
    { value: 'video', label: 'Video' },
    { value: 'audio', label: 'Audio' },
    { value: 'file', label: 'Files' },
];

export interface MediaLibraryDialogProps {
    open: boolean;
    onClose: () => void;
    onSelect: (items: MediaItem[]) => void;
    multiple?: boolean;
    accept?: MediaType | 'all';
    group?: string;
    endpoints?: Partial<MediaEndpoints>;
}

/**
 * Grid media browser/picker used by the attach & picture fields to reuse
 * existing assets. Supports search, type filtering, upload and delete.
 */
export function MediaLibraryDialog({
    open,
    onClose,
    onSelect,
    multiple = false,
    accept = 'all',
    group,
    endpoints: endpointsOverride,
}: MediaLibraryDialogProps) {
    const t = useT();
    const endpoints = useMediaEndpoints(endpointsOverride);
    const [items, setItems] = useState<MediaItem[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [search, setSearch] = useState('');
    const [type, setType] = useState<MediaType | 'all'>(accept);
    const [selected, setSelected] = useState<Record<string, MediaItem>>({});
    const fileInput = useRef<HTMLInputElement>(null);

    const load = useCallback(() => {
        setLoading(true);
        setError(null);

        listMedia(endpoints, { type, search, group })
            .then((response) => setItems(response.data))
            .catch((reason: unknown) =>
                setError(reason instanceof Error ? reason.message : t('Failed to load media')),
            )
            .finally(() => setLoading(false));
    }, [endpoints, type, search, group, t]);

    useEffect(() => {
        if (open) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            load();
        }
    }, [open, load]);

    useEffect(() => {
        if (!open) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setSelected({});
        }
    }, [open]);

    const toggle = (item: MediaItem) => {
        const key = String(item.id);

        setSelected((current) => {
            if (current[key]) {
                const next = { ...current };
                delete next[key];

                return next;
            }

            return multiple ? { ...current, [key]: item } : { [key]: item };
        });
    };

    const handleUpload = (files: FileList | null) => {
        if (!files || files.length === 0) {
            return;
        }

        setLoading(true);
        uploadMedia(endpoints, files, group ? { group } : {})
            .then((uploaded) => setItems((current) => [...uploaded, ...current]))
            .catch((reason: unknown) =>
                setError(reason instanceof Error ? reason.message : t('Upload failed')),
            )
            .finally(() => setLoading(false));
    };

    const handleDelete = (item: MediaItem) => {
        deleteMedia(endpoints, item.id)
            .then(() => {
                setItems((current) => current.filter((entry) => entry.id !== item.id));
                setSelected((current) => {
                    const next = { ...current };
                    delete next[String(item.id)];

                    return next;
                });
            })
            .catch((reason: unknown) =>
                setError(reason instanceof Error ? reason.message : t('Delete failed')),
            );
    };

    const selectedItems = Object.values(selected);

    return (
        <Overlay
            open={open}
            onClose={onClose}
            size="xl"
            title={t('Media library')}
            footer={
                <>
                    <span className="mr-auto text-xs text-gray-400">
                        {selectedItems.length > 0 ? t(':count selected', { count: selectedItems.length }) : null}
                    </span>
                    <UiButton type="button" variant="default" onClick={onClose}>
                        {t('Cancel')}
                    </UiButton>
                    <UiButton
                        type="button"
                        variant="primary"
                        disabled={selectedItems.length === 0}
                        onClick={() => {
                            onSelect(selectedItems);
                            onClose();
                        }}
                    >
                        {t('Use selected')}
                    </UiButton>
                </>
            }
        >
            <div className="mb-4 flex flex-wrap items-center gap-2">
                <div className="flex gap-1">
                    {TYPE_TABS.map((tab) => (
                        <button
                            key={tab.value}
                            type="button"
                            onClick={() => setType(tab.value)}
                            className={cn(
                                'rounded-md px-2.5 py-1 text-sm',
                                type === tab.value
                                    ? 'bg-orbit-primary/10 font-medium text-orbit-primary'
                                    : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700',
                            )}
                        >
                            {t(tab.label)}
                        </button>
                    ))}
                </div>
                <input
                    type="search"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={t('Search…')}
                    className="ml-auto w-48 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                />
                <UiButton type="button" variant="default" onClick={() => fileInput.current?.click()}>
                    {t('Upload')}
                </UiButton>
                <input
                    ref={fileInput}
                    type="file"
                    multiple
                    className="hidden"
                    onChange={(event) => {
                        handleUpload(event.target.files);
                        event.target.value = '';
                    }}
                />
            </div>

            {error ? (
                <p className="mb-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-500/10">
                    {error}
                </p>
            ) : null}

            {loading && items.length === 0 ? (
                <MediaSkeleton />
            ) : items.length === 0 ? (
                <p className="py-12 text-center text-sm text-gray-400">{t('No media found.')}</p>
            ) : (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    {items.map((item) => (
                        <MediaCard
                            key={item.id}
                            item={item}
                            selected={Boolean(selected[String(item.id)])}
                            onToggle={() => toggle(item)}
                            onDelete={() => handleDelete(item)}
                        />
                    ))}
                </div>
            )}
        </Overlay>
    );
}

function MediaCard({
    item,
    selected,
    onToggle,
    onDelete,
}: {
    item: MediaItem;
    selected: boolean;
    onToggle: () => void;
    onDelete: () => void;
}) {
    const t = useT();
    const mediaType = inferMediaType(item);
    const encoding = item.encoding_status;

    return (
        <div
            className={cn(
                'group relative cursor-pointer overflow-hidden rounded-lg border bg-white dark:bg-gray-900',
                selected ? 'border-orbit-primary ring-2 ring-orbit-primary/40' : 'border-gray-200 dark:border-gray-700',
            )}
            onClick={onToggle}
        >
            <div className="flex aspect-square items-center justify-center bg-gray-50 dark:bg-gray-800">
                {mediaType === 'image' ? (
                    <img
                        src={item.thumbnail ?? item.url}
                        alt={item.original_name ?? item.name}
                        className="h-full w-full object-cover"
                        loading="lazy"
                    />
                ) : (
                    <MediaTypeBadge type={mediaType} />
                )}
            </div>
            <div className="px-2 py-1.5">
                <p className="truncate text-xs text-gray-700 dark:text-gray-200" title={item.original_name ?? item.name}>
                    {item.original_name ?? item.name}
                </p>
                <p className="text-[10px] text-gray-400">{formatBytes(item.size)}</p>
            </div>

            {encoding && encoding !== 'done' ? (
                <span className="absolute left-1 top-1 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-medium text-white">
                    {encoding === 'failed' ? t('Encoding failed') : t('Encoding…')}
                </span>
            ) : null}

            <button
                type="button"
                onClick={(event) => {
                    event.stopPropagation();
                    onDelete();
                }}
                className="absolute right-1 top-1 hidden rounded bg-black/50 px-1.5 py-0.5 text-[10px] text-white group-hover:block hover:bg-red-600"
            >
                {t('Delete')}
            </button>
        </div>
    );
}

function MediaTypeBadge({ type }: { type: MediaType }) {
    const t = useT();
    const label = type === 'video' ? t('Video') : type === 'audio' ? t('Audio') : t('File');

    return (
        <span className="rounded bg-gray-200 px-2 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">
            {label}
        </span>
    );
}

function MediaSkeleton() {
    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            {Array.from({ length: 10 }).map((_, index) => (
                <div
                    key={index}
                    className="aspect-square animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"
                />
            ))}
        </div>
    );
}
