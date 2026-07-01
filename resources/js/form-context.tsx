import type { FormDataConvertibleValue } from '@inertiajs/core';
import { useForm } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useMemo
    
} from 'react';
import type {ReactNode} from 'react';

/**
 * Form values are intentionally typed as a flat record of convertible *values*
 * (not the deeply-recursive FormDataConvertible) so Inertia's FormDataType<T>
 * constraint stays shallow and does not trigger "excessively deep" inference.
 * Nested structures are handled at runtime via path helpers and cast locally.
 */
type FormData = Record<string, FormDataConvertibleValue>;

interface OrbitFormContextValue {
    /** Read a value by field name (supports `model[title]` / `model.title`). */
    getValue: (name: string | null) => unknown;
    /** Write a value by field name (supports bracket / dot notation). */
    setValue: (name: string | null, value: unknown) => void;
    /** Validation errors for a field name. */
    getError: (name: string | null) => string | undefined;
    processing: boolean;
    isDirty: boolean;
    submit: (method: HttpMethod, url: string) => void;
}

type HttpMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

const OrbitFormContext = createContext<OrbitFormContextValue | null>(null);

/** Convert a field name (`model[title]`, `model.title`, `tags[]`) into a path. */
export function nameToPath(name: string): string[] {
    return name
        .replace(/\]/g, '')
        .replace(/\[/g, '.')
        .split('.')
        .filter((segment) => segment.length > 0);
}

function getByPath(source: FormData, path: string[]): unknown {
    return path.reduce<unknown>((carry, key) => {
        if (carry && typeof carry === 'object' && key in (carry as object)) {
            return (carry as Record<string, unknown>)[key];
        }

        return undefined;
    }, source);
}

function setByPath(source: FormData, path: string[], value: unknown): FormData {
    if (path.length === 0) {
        return source;
    }

    const [head, ...rest] = path;
    const next: FormData = { ...source };

    if (rest.length === 0) {
        next[head] = value as FormDataConvertibleValue;

        return next;
    }

    const child = (next[head] && typeof next[head] === 'object'
        ? next[head]
        : {}) as FormData;
    next[head] = setByPath(child, rest, value) as unknown as FormDataConvertibleValue;

    return next;
}

export function FormProvider({
    initialData,
    state,
    children,
}: {
    initialData: Record<string, unknown>;
    state: string | null;
    children: ReactNode;
}) {
    const seed = useMemo<FormData>(
        () => ({ ...(initialData as FormData), _state: state ?? undefined }),
        [initialData, state],
    );

    const form = useForm(seed);
    const { data, setData, errors, processing, isDirty } = form;

    const getValue = useCallback(
        (name: string | null) => (name ? getByPath(data, nameToPath(name)) : undefined),
        [data],
    );

    const setValue = useCallback(
        (name: string | null, value: unknown) => {
            if (!name) {
                return;
            }

            setData((current) => setByPath(current, nameToPath(name), value));
        },
        [setData],
    );

    const getError = useCallback(
        (name: string | null) => {
            if (!name) {
                return undefined;
            }

            const dotted = nameToPath(name).join('.');

            return (errors as Record<string, string>)[dotted] ?? (errors as Record<string, string>)[name];
        },
        [errors],
    );

    const submit = useCallback(
        (method: HttpMethod, url: string) => {
            form.submit(method, url, { preserveScroll: true });
        },
        [form],
    );

    const value = useMemo<OrbitFormContextValue>(
        () => ({ getValue, setValue, getError, processing, isDirty, submit }),
        [getValue, setValue, getError, processing, isDirty, submit],
    );

    return <OrbitFormContext.Provider value={value}>{children}</OrbitFormContext.Provider>;
}

export function useOrbitForm(): OrbitFormContextValue {
    const context = useContext(OrbitFormContext);

    if (!context) {
        throw new Error('useOrbitForm must be used within an Orbit <FormProvider>.');
    }

    return context;
}

/** Optional variant that does not throw when used outside a form (read-only screens). */
export function useOptionalOrbitForm(): OrbitFormContextValue | null {
    return useContext(OrbitFormContext);
}
