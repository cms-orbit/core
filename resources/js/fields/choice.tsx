import { useEffect, useMemo, useRef, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr, bool, normalizeOptions, str } from './shared';
import type { OptionEntry } from './shared';

export function RadioField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const options = normalizeOptions(attr(props, 'options'));
    const inline = bool(attr(props, 'inline'));

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className={cn('flex gap-4', inline ? 'flex-row flex-wrap' : 'flex-col')}>
                {options.map((option) => (
                    <label key={option.value} className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input
                            type="radio"
                            name={name ?? undefined}
                            value={option.value}
                            checked={str(value) === option.value}
                            onChange={() => onChange?.(option.value)}
                            className="h-4 w-4 border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                        />
                        {option.label}
                    </label>
                ))}
            </div>
        </FieldShell>
    );
}

export function RadioButtonsField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const options = normalizeOptions(attr(props, 'options'));

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="inline-flex flex-wrap overflow-hidden rounded-md border border-gray-300 dark:border-gray-700">
                {options.map((option, index) => {
                    const active = str(value) === option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            name={name ?? undefined}
                            onClick={() => onChange?.(option.value)}
                            className={cn(
                                'px-3 py-1.5 text-sm',
                                index > 0 && 'border-l border-gray-300 dark:border-gray-700',
                                active
                                    ? 'bg-orbit-primary text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800',
                            )}
                        >
                            {option.label}
                        </button>
                    );
                })}
            </div>
        </FieldShell>
    );
}

function toArrayValue(value: unknown): string[] {
    if (Array.isArray(value)) {
        return value
            .map((item) => {
                if (item && typeof item === 'object') {
                    const record = item as Record<string, unknown>;
                    const scalar = record.id ?? record.value ?? null;

                    return scalar === null || scalar === undefined ? '' : str(scalar);
                }

                return str(item);
            })
            .filter(Boolean);
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    if (typeof value === 'object') {
        return Object.keys(value as Record<string, unknown>);
    }

    return [str(value)];
}

/**
 * Unified select field (Orchid Select successor). Supports single & multiple
 * selection, search, empty option and tag creation without external deps.
 */
export function SelectField(props: FieldComponentProps) {
    const t = useT();
    const { name, value, errors, onChange } = props;
    const baseOptions = useMemo(() => normalizeOptions(attr(props, 'options')), [props]);

    const isMultiple =
        bool(attr(props, 'multiple')) ||
        bool(attr(props, 'tags')) ||
        Array.isArray(value) ||
        (typeof name === 'string' && name.endsWith('[]'));
    const allowCreate = bool(attr(props, 'allowCreate')) || bool(attr(props, 'allowCreateValue'));
    const allowEmpty = bool(attr(props, 'allowEmpty')) || bool(attr(props, 'allowEmptyValue'));
    const placeholder = attr<string>(props, 'placeholder');

    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);

    const selectedValues = isMultiple ? toArrayValue(value) : [str(value)].filter(Boolean);

    const [extraOptions, setExtraOptions] = useState<OptionEntry[]>([]);
    const options = useMemo(() => {
        const merged = [...baseOptions, ...extraOptions];
        const known = new Set(merged.map((option) => option.value));
        const synthetic = selectedValues
            .filter((selected) => !known.has(selected))
            .map((selected) => ({ value: selected, label: selected }));

        return [...merged, ...synthetic];
    }, [baseOptions, extraOptions, selectedValues]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handler = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handler);

        return () => document.removeEventListener('mousedown', handler);
    }, [open]);

    const labelFor = (optionValue: string) =>
        options.find((option) => option.value === optionValue)?.label ?? optionValue;

    const filtered = options.filter((option) =>
        option.label.toLowerCase().includes(search.toLowerCase()),
    );

    const commit = (next: string[]) => {
        onChange?.(isMultiple ? next : (next[0] ?? null));
    };

    const select = (optionValue: string) => {
        if (isMultiple) {
            const exists = selectedValues.includes(optionValue);
            commit(exists ? selectedValues.filter((v) => v !== optionValue) : [...selectedValues, optionValue]);
        } else {
            commit([optionValue]);
            setOpen(false);
        }

        setSearch('');
    };

    const createTag = () => {
        const trimmed = search.trim();

        if (!trimmed || selectedValues.includes(trimmed)) {
            return;
        }

        setExtraOptions((current) => [...current, { value: trimmed, label: trimmed }]);
        select(trimmed);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div ref={containerRef} className="relative">
                <div
                    className={cn(inputClass, 'flex min-h-[2.375rem] cursor-pointer flex-wrap items-center gap-1')}
                    onClick={() => setOpen((value) => !value)}
                >
                    {isMultiple && selectedValues.length > 0 ? (
                        selectedValues.map((selected) => (
                            <span
                                key={selected}
                                className="inline-flex items-center gap-1 rounded bg-orbit-primary/10 px-1.5 py-0.5 text-xs text-orbit-primary"
                            >
                                {labelFor(selected)}
                                <button
                                    type="button"
                                    onClick={(event) => {
                                        event.stopPropagation();
                                        commit(selectedValues.filter((v) => v !== selected));
                                    }}
                                >
                                    &times;
                                </button>
                            </span>
                        ))
                    ) : !isMultiple && selectedValues[0] ? (
                        <span className="text-sm text-gray-900 dark:text-gray-100">{labelFor(selectedValues[0])}</span>
                    ) : (
                        <span className="text-sm text-gray-400">{placeholder ?? t('Select…')}</span>
                    )}
                </div>

                {open ? (
                    <div className="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <input
                            autoFocus
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter' && allowCreate) {
                                    event.preventDefault();
                                    createTag();
                                }
                            }}
                            placeholder={t('Search…')}
                            className="mb-1 w-full border-b border-gray-100 px-3 py-1.5 text-sm outline-none dark:border-gray-700 dark:bg-gray-800"
                        />
                        {allowEmpty && !isMultiple ? (
                            <button
                                type="button"
                                className="block w-full px-3 py-1.5 text-left text-sm text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                                onClick={() => select('')}
                            >
                                —
                            </button>
                        ) : null}
                        {filtered.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => select(option.value)}
                                className={cn(
                                    'block w-full px-3 py-1.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700',
                                    selectedValues.includes(option.value) && 'font-medium text-orbit-primary',
                                )}
                            >
                                {option.label}
                            </button>
                        ))}
                        {allowCreate && search.trim() && !filtered.some((o) => o.label === search.trim()) ? (
                            <button
                                type="button"
                                onClick={createTag}
                                className="block w-full px-3 py-1.5 text-left text-sm text-orbit-primary hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                {t('Create “:value”', { value: search.trim() })}
                            </button>
                        ) : null}
                        {filtered.length === 0 && !allowCreate ? (
                            <p className="px-3 py-2 text-sm text-gray-400">{t('No options')}</p>
                        ) : null}
                    </div>
                ) : null}
            </div>
        </FieldShell>
    );
}

/** Time zone selector — same UX as Select, options provided by the backend. */
export function TimeZoneField(props: FieldComponentProps) {
    return <SelectField {...props} />;
}
