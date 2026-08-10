export type MediaType = 'image' | 'video' | 'audio' | 'file';

export type EncodingStatus = 'pending' | 'processing' | 'done' | 'failed' | null;

/**
 * A reusable media asset. The backend media endpoints serialize attachments
 * into this shape.
 */
export interface MediaItem {
    id: number | string;
    name: string;
    original_name?: string | null;
    url: string;
    relativeUrl?: string | null;
    thumbnail?: string | null;
    mime?: string | null;
    extension?: string | null;
    /** Size in bytes. */
    size?: number | null;
    group?: string | null;
    disk?: string | null;
    width?: number | null;
    height?: number | null;
    /** Present for video assets when ffmpeg encoding is configured. */
    encoding_status?: EncodingStatus;
    created_at?: string | null;
}

export interface MediaListResponse {
    data: MediaItem[];
    meta?: {
        current_page?: number;
        last_page?: number;
        total?: number;
    };
}

/**
 * REST-ish media endpoints under the Orbit admin prefix. Defaults assume:
 *   GET    {index}?type=&search=&page=  -> MediaListResponse
 *   POST   {upload} (multipart "files[]") -> { data: MediaItem | MediaItem[] }
 *   DELETE {index}/{id}                  -> 204
 * The backend worker documents the concrete paths in CONTRACT.md.
 */
export interface MediaEndpoints {
    index: string;
    upload: string;
    /** Base for delete; the id is appended as `${remove}/${id}`. */
    remove: string;
}

export const DEFAULT_MEDIA_ENDPOINTS: MediaEndpoints = {
    index: '/orbit/api/media',
    upload: '/orbit/api/media',
    remove: '/orbit/api/media',
};

export interface MediaQuery {
    type?: MediaType | 'all';
    search?: string;
    page?: number;
    group?: string;
}

export function inferMediaType(item: MediaItem): MediaType {
    const mime = item.mime ?? '';

    if (mime.startsWith('image/')) {
        return 'image';
    }

    if (mime.startsWith('video/')) {
        return 'video';
    }

    if (mime.startsWith('audio/')) {
        return 'audio';
    }

    return 'file';
}

export function formatBytes(bytes?: number | null): string {
    if (!bytes || bytes <= 0) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, exponent);

    return `${value.toFixed(value >= 10 || exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}
