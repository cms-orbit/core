import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

export interface OrbitLocaleOption {
    code: string;
    label: string;
}

export interface OrbitI18n {
    /** Active admin interface locale (e.g. "ko"). */
    locale: string;
    /** Key => translated string map for the active locale. */
    messages: Record<string, string>;
    /** Locales offered in the language switcher. */
    available: OrbitLocaleOption[];
    /** URL the switcher POSTs to in order to change the locale. */
    switchUrl?: string | null;
}

interface PageProps {
    orbit?: { i18n?: OrbitI18n };
    [key: string]: unknown;
}

export type Replacements = Record<string, string | number>;

/**
 * Pure translator: resolves a key against a messages map and interpolates
 * `:name` placeholders. Falls back to the key itself (English source string).
 */
export function translate(
    messages: Record<string, string>,
    key: string,
    replace?: Replacements,
): string {
    let text = messages[key] ?? key;

    if (replace) {
        for (const [token, value] of Object.entries(replace)) {
            text = text.replace(new RegExp(`:${token}`, 'g'), String(value));
        }
    }

    return text;
}

/** Read the shared `orbit.i18n` payload from the Inertia page props. */
export function useI18n(): OrbitI18n {
    const page = usePage<PageProps>();

    return (
        page.props.orbit?.i18n ?? {
            locale: 'en',
            messages: {},
            available: [],
            switchUrl: null,
        }
    );
}

/**
 * Returns a memoised `t()` translator bound to the current locale's messages.
 * Usage: const t = useT(); t('Save'); t('The :resource was updated!', { resource: 'User' }).
 */
export function useT() {
    const { messages } = useI18n();

    return useCallback(
        (key: string, replace?: Replacements) => translate(messages, key, replace),
        [messages],
    );
}
