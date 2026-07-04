import type { FieldComponentProps } from '../contract';

export interface OptionEntry {
    value: string;
    label: string;
}

export function str(value: unknown): string {
    return value === null || value === undefined ? '' : String(value);
}

export function attr<T = unknown>(props: FieldComponentProps, key: string): T | undefined {
    return props.attributes[key] as T | undefined;
}

export function bool(value: unknown): boolean {
    if (typeof value === 'string') {
        return value === 'true' || value === '1';
    }

    return Boolean(value);
}

export function objectValue(value: unknown): Record<string, unknown> | null {
    return value && typeof value === 'object' && !Array.isArray(value) ? (value as Record<string, unknown>) : null;
}

/** Normalize Orchid option attributes (list array or {value: label} map). */
export function normalizeOptions(raw: unknown): OptionEntry[] {
    if (Array.isArray(raw)) {
        return raw.map((item) => ({ value: str(item), label: str(item) }));
    }

    if (raw && typeof raw === 'object') {
        return Object.entries(raw as Record<string, unknown>).map(([value, label]) => ({
            value,
            label: str(label),
        }));
    }

    return [];
}
