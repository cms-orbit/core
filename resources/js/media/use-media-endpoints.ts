import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { DEFAULT_MEDIA_ENDPOINTS } from './types';
import type { MediaEndpoints } from './types';

interface MediaPageProps {
    orbit?: { media?: Partial<MediaEndpoints> };
    [key: string]: unknown;
}

/**
 * Resolves the media endpoints, preferring the shared `orbit.media` Inertia
 * prop (set by the backend) and falling back to sensible defaults.
 */
export function useMediaEndpoints(override?: Partial<MediaEndpoints>): MediaEndpoints {
    const page = usePage<MediaPageProps>();
    const shared = page.props.orbit?.media;

    return useMemo(
        () => ({ ...DEFAULT_MEDIA_ENDPOINTS, ...shared, ...override }),
        [shared, override],
    );
}
