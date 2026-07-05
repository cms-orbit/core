const DEFAULT_COOKIE_MAX_AGE = '31536000';

/** Read a browser cookie value by name. */
export function readCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

/** Persist a browser cookie scoped to the current site root. */
export function writeCookie(name: string, value: string, maxAge = DEFAULT_COOKIE_MAX_AGE): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.cookie = `${name}=${encodeURIComponent(value)}; path=/; max-age=${maxAge}; SameSite=Lax`;
}
