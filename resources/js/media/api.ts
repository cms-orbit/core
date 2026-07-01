import { orbitFetch } from '../lib/http';
import type {
    MediaEndpoints,
    MediaItem,
    MediaListResponse,
    MediaQuery,
} from './types';

export async function listMedia(
    endpoints: MediaEndpoints,
    query: MediaQuery = {},
): Promise<MediaListResponse> {
    const params = new URLSearchParams();

    if (query.type && query.type !== 'all') {
        params.set('type', query.type);
    }

    if (query.search) {
        params.set('search', query.search);
    }

    if (query.page) {
        params.set('page', String(query.page));
    }

    if (query.group) {
        params.set('group', query.group);
    }

    const queryString = params.toString();
    const url = queryString ? `${endpoints.index}?${queryString}` : endpoints.index;

    return orbitFetch<MediaListResponse>(url);
}

export async function uploadMedia(
    endpoints: MediaEndpoints,
    files: FileList | File[],
    extra: Record<string, string> = {},
): Promise<MediaItem[]> {
    const body = new FormData();

    Array.from(files).forEach((file) => body.append('files[]', file));

    Object.entries(extra).forEach(([key, value]) => body.append(key, value));

    const response = await orbitFetch<{ data: MediaItem | MediaItem[] }>(endpoints.upload, {
        method: 'POST',
        body,
    });

    return Array.isArray(response.data) ? response.data : [response.data];
}

export async function deleteMedia(
    endpoints: MediaEndpoints,
    id: number | string,
): Promise<void> {
    await orbitFetch<void>(`${endpoints.remove}/${id}`, { method: 'DELETE' });
}
