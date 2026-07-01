/** Read a browser cookie value (used for Laravel's XSRF token). */
export function readCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function baseHeaders(extra?: HeadersInit): Headers {
    const headers = new Headers(extra);
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');

    const token = readCookie('XSRF-TOKEN');

    if (token) {
        headers.set('X-XSRF-TOKEN', token);
    }

    return headers;
}

/** Fetch JSON with Laravel-friendly headers (CSRF, XHR, same-origin). */
export async function orbitFetch<T>(url: string, init: RequestInit = {}): Promise<T> {
    const headers = baseHeaders(init.headers);

    if (init.body && !(init.body instanceof FormData) && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...init,
        headers,
    });

    if (!response.ok) {
        throw new Error(`Request failed (${response.status})`);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}
