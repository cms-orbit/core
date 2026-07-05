export const TRANSPARENT_COLOR = 'transparent';

export function isTransparentColor(value: string | null | undefined): boolean {
    if (value === null || value === undefined) {
        return false;
    }

    const normalized = value.trim().toLowerCase();

    return normalized === TRANSPARENT_COLOR || normalized === 'none';
}

export function resolveOpaqueColor(value: string | undefined, fallback: string): string {
    if (!value || isTransparentColor(value)) {
        return fallback;
    }

    return value;
}
