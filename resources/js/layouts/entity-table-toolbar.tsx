import { Link, router, usePage } from '@inertiajs/react';
import type { PointerEvent as ReactPointerEvent, ReactNode } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ColumnNode, FieldNode } from '../contract';
import { SelectField } from '../fields/choice';
import { FormProvider, useOrbitForm } from '../form-context';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { FieldRenderer } from '../screen-renderer';
import { UiButton } from '../ui/button';
import { Badge } from '../ui/badge';
import { Icon } from '../ui/icon';

type PaginationLinkItem =
    | { type: 'page'; page: number; url: string; active: boolean }
    | { type: 'ellipsis' };

const FILTER_POPOVER_DEFAULT_HEIGHT = 280;
const FILTER_POPOVER_MIN_HEIGHT = 200;
const FILTER_POPOVER_MAX_HEIGHT = 640;
const FILTER_POPOVER_HEIGHT_STORAGE_KEY = 'orbit.table.filterPopoverHeight';

function readStoredFilterPopoverHeight(): number {
    if (typeof window === 'undefined') {
        return FILTER_POPOVER_DEFAULT_HEIGHT;
    }

    const stored = window.localStorage.getItem(FILTER_POPOVER_HEIGHT_STORAGE_KEY);

    if (!stored) {
        return FILTER_POPOVER_DEFAULT_HEIGHT;
    }

    const parsed = Number(stored);

    if (!Number.isFinite(parsed)) {
        return FILTER_POPOVER_DEFAULT_HEIGHT;
    }

    return Math.min(FILTER_POPOVER_MAX_HEIGHT, Math.max(FILTER_POPOVER_MIN_HEIGHT, parsed));
}

function resolveFilterPopoverMaxHeight(): number {
    if (typeof window === 'undefined') {
        return FILTER_POPOVER_MAX_HEIGHT;
    }

    return Math.min(FILTER_POPOVER_MAX_HEIGHT, Math.round(window.innerHeight * 0.75));
}

function readSearchParam(column: string): string {
    if (typeof window === 'undefined') {
        return '';
    }

    const params = new URLSearchParams(window.location.search);

    return params.get(`filter[${column}]`) ?? '';
}

function uniqueFilterValues(values: string[]): string[] {
    return [...new Set(values.filter(Boolean))];
}

function searchFromPageUrl(pageUrl?: string): string {
    if (pageUrl && pageUrl.includes('?')) {
        return pageUrl.split('?')[1] ?? '';
    }

    if (typeof window !== 'undefined') {
        return window.location.search.slice(1);
    }

    return '';
}

/** Read multiselect filter values from a query string (URL is the source of truth). */
function readInlineFilterFromSearch(name: string | null | undefined, pageUrl?: string): string[] {
    if (!name) {
        return [];
    }

    const search = searchFromPageUrl(pageUrl);

    if (search === '') {
        return [];
    }

    const params = new URLSearchParams(search);
    const scalar = params.get(name);

    if (scalar !== null && scalar !== '') {
        return scalar.includes(',')
            ? uniqueFilterValues(scalar.split(',').map((item) => item.trim()))
            : [scalar];
    }

    return readLegacyIndexedFilterParams(params, name);
}

/** Read multiselect filter values from the current location. */
function readInlineFilterFromLocation(name: string | null | undefined): string[] {
    return readInlineFilterFromSearch(name);
}

/** Backwards compatibility for indexed / nested legacy filter URLs. */
function readLegacyIndexedFilterParams(params: URLSearchParams, name: string): string[] {
    const values: string[] = [];

    params.forEach((value, key) => {
        if (value === '') {
            return;
        }

        if (key.startsWith(`${name}[`)) {
            values.push(value);
        }
    });

    return uniqueFilterValues(values);
}

function paramsToFlatQueryObject(params: URLSearchParams): Record<string, string> {
    const data: Record<string, string> = {};

    params.forEach((value, key) => {
        data[key] = value;
    });

    return data;
}

function clearFilterParamKeys(params: URLSearchParams, filterName: string): void {
    for (const key of [...params.keys()]) {
        if (isFilterParamKey(key, filterName)) {
            params.delete(key);
        }
    }
}

function visitTableGet(
    mutator: (params: URLSearchParams) => void,
    options: NonNullable<Parameters<typeof router.get>[2]> = {},
) {
    const params = new URLSearchParams(window.location.search);
    mutator(params);

    router.get(window.location.pathname, paramsToFlatQueryObject(params), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        ...options,
    });
}

function serializeFilterFieldValue(value: unknown): { scalar?: string; range?: Record<string, string> } {
    if (value === null || value === undefined || value === '') {
        return {};
    }

    if (Array.isArray(value)) {
        const flat = uniqueFilterValues(value.map(String));

        return flat.length > 0 ? { scalar: flat.join(',') } : {};
    }

    if (typeof value === 'object') {
        const range: Record<string, string> = {};

        Object.entries(value as Record<string, unknown>).forEach(([key, subValue]) => {
            if (subValue !== null && subValue !== undefined && subValue !== '') {
                range[key] = String(subValue);
            }
        });

        return Object.keys(range).length > 0 ? { range } : {};
    }

    return { scalar: String(value) };
}

function appendFilterFieldToParams(params: URLSearchParams, name: string, value: unknown): void {
    clearFilterParamKeys(params, name);

    const serialized = serializeFilterFieldValue(value);

    if (serialized.scalar !== undefined) {
        params.set(name, serialized.scalar);

        return;
    }

    if (serialized.range) {
        Object.entries(serialized.range).forEach(([key, subValue]) => {
            params.set(`${name}[${key}]`, subValue);
        });
    }
}

function isFilterParamKey(key: string, filterName: string): boolean {
    return key === filterName || key.startsWith(`${filterName}[`);
}

function visitWithInlineFilter(
    filterName: string,
    values: string[],
    options: NonNullable<Parameters<typeof router.get>[2]> = {},
) {
    const unique = uniqueFilterValues(values);

    visitTableGet((params) => {
        clearFilterParamKeys(params, filterName);

        if (unique.length > 0) {
            params.set(filterName, unique.join(','));
        }

        params.delete('page');
    }, options);
}

function hasFilterValue(params: URLSearchParams, name: string): boolean {
    const scalar = params.get(name);

    if (scalar !== null && scalar !== '') {
        return true;
    }

    return readLegacyIndexedFilterParams(params, name).length > 0;
}

function toSelectedValues(value: unknown): string[] {
    if (Array.isArray(value)) {
        return value.map((item) => String(item)).filter(Boolean);
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    return [String(value)];
}

function countActiveFilters(fields: FieldNode[]): number {
    if (typeof window === 'undefined') {
        return 0;
    }

    const params = new URLSearchParams(window.location.search);

    return fields.reduce((count, field) => {
        const name = field.name;

        if (!name) {
            return count;
        }

        return hasFilterValue(params, name) ? count + 1 : count;
    }, 0);
}

function InlineTableFilter({ field }: { field: FieldNode }) {
    const fieldName = field.name;
    const { url } = usePage();
    const placeholder =
        (field.attributes?.placeholder as string | undefined) ??
        (field.attributes?.title as string | undefined) ??
        '';
    const [selected, setSelected] = useState<string[]>(() => uniqueFilterValues(toSelectedValues(field.value)));

    useEffect(() => {
        if (!fieldName) {
            return;
        }

        setSelected(readInlineFilterFromSearch(fieldName, url));
    }, [fieldName, url]);

    const apply = (values: string[]) => {
        if (!fieldName) {
            return;
        }

        const next = uniqueFilterValues(values);

        setSelected(next);

        visitWithInlineFilter(fieldName, next, {
            onFinish: () => {
                setSelected(readInlineFilterFromLocation(fieldName));
            },
        });
    };

    return (
        <div className="min-w-[11rem] max-w-xs">
            <SelectField
                node={field}
                data={{}}
                name={fieldName}
                value={selected}
                attributes={{
                    ...field.attributes,
                    title: null,
                    placeholder,
                    compact: true,
                    searchable: true,
                    multiple: true,
                }}
                errors={[]}
                onChange={(next) => {
                    apply(uniqueFilterValues(toSelectedValues(next)));
                }}
            />
        </div>
    );
}

function InlineTableFilters({ fields }: { fields: FieldNode[] }) {
    if (fields.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {fields.map((field, index) => (
                <InlineTableFilter key={field.name ?? `inline-filter-${index}`} field={field} />
            ))}
        </div>
    );
}

function FilterPopoverPanel({
    fields,
    onClose,
}: {
    fields: FieldNode[];
    onClose: () => void;
}) {
    const t = useT();
    const [height, setHeight] = useState(readStoredFilterPopoverHeight);
    const [isResizing, setIsResizing] = useState(false);
    const resizeStartY = useRef(0);
    const resizeStartHeight = useRef(FILTER_POPOVER_DEFAULT_HEIGHT);

    useEffect(() => {
        if (!isResizing) {
            return;
        }

        const maxHeight = resolveFilterPopoverMaxHeight();

        const onPointerMove = (event: PointerEvent) => {
            const delta = event.clientY - resizeStartY.current;
            const next = Math.min(
                maxHeight,
                Math.max(FILTER_POPOVER_MIN_HEIGHT, resizeStartHeight.current + delta),
            );

            setHeight(next);
        };

        const onPointerUp = () => {
            setIsResizing(false);
            setHeight((current) => {
                window.localStorage.setItem(FILTER_POPOVER_HEIGHT_STORAGE_KEY, String(current));

                return current;
            });
        };

        document.body.style.userSelect = 'none';
        document.body.style.cursor = 'nwse-resize';
        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);

        return () => {
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            document.removeEventListener('pointermove', onPointerMove);
            document.removeEventListener('pointerup', onPointerUp);
        };
    }, [isResizing]);

    const startResize = (event: ReactPointerEvent<HTMLButtonElement>) => {
        event.preventDefault();
        event.stopPropagation();
        resizeStartY.current = event.clientY;
        resizeStartHeight.current = height;
        setIsResizing(true);
    };

    return (
        <div
            className="relative flex w-[min(100vw-2rem,22rem)] flex-col overflow-hidden rounded-lg border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] shadow-lg"
            style={{ height: `${height}px` }}
        >
            <div className="flex shrink-0 items-center justify-between border-b border-[var(--color-orbit-panel-border,#e2e8f0)] px-4 py-3">
                <p className="text-sm font-semibold text-[var(--color-orbit-secondary,#334155)]">{t('Filters')}</p>
                <button
                    type="button"
                    className="text-xs text-[var(--color-orbit-nav-group-fg,#94a3b8)] hover:text-[var(--color-orbit-secondary,#64748b)]"
                    onClick={onClose}
                >
                    {t('Close')}
                </button>
            </div>

            <div className="flex min-h-0 flex-1 flex-col px-4 py-3">
                <FormProvider initialData={{}} state={null}>
                    <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
                        <div className="space-y-3 pb-1">
                            {fields.map((field, index) => (
                                <FieldRenderer
                                    key={field.name ?? `${field.component}-${index}`}
                                    node={field}
                                    data={{}}
                                />
                            ))}
                        </div>
                    </div>
                    <FilterPopoverActions fields={fields} onClose={onClose} />
                </FormProvider>
            </div>

            <button
                type="button"
                aria-label={t('Resize filters panel')}
                onPointerDown={startResize}
                className={cn(
                    'absolute bottom-0 right-0 z-10 flex h-5 w-5 cursor-nwse-resize items-end justify-end p-0.5',
                    'text-[var(--color-orbit-nav-group-fg,#94a3b8)] hover:text-[var(--color-orbit-secondary,#64748b)]',
                    isResizing && 'text-[var(--color-orbit-secondary,#64748b)]',
                )}
            >
                <svg viewBox="0 0 12 12" className="h-3 w-3 shrink-0" aria-hidden="true">
                    <path
                        d="M11 11V7.5M11 11H7.5M11 11L7 7"
                        fill="none"
                        stroke="currentColor"
                        strokeLinecap="round"
                        strokeWidth="1.5"
                    />
                </svg>
            </button>
        </div>
    );
}

function FilterPopoverActions({
    fields,
    onClose,
}: {
    fields: FieldNode[];
    onClose: () => void;
}) {
    const t = useT();
    const form = useOrbitForm();

    const apply = () => {
        visitTableGet((params) => {
            params.delete('page');

            for (const field of fields) {
                if (!field.name) {
                    continue;
                }

                appendFilterFieldToParams(params, field.name, form.getValue(field.name));
            }
        });

        onClose();
    };

    const reset = () => {
        router.get(window.location.pathname, {}, { preserveScroll: true, replace: true });
        onClose();
    };

    return (
        <div className="mt-3 flex shrink-0 items-center gap-2 border-t border-[var(--color-orbit-panel-border,#e2e8f0)] pt-3">
            <UiButton type="button" variant="primary" size="sm" onClick={apply}>
                {t('Apply filters')}
            </UiButton>
            <button
                type="button"
                onClick={reset}
                className="text-xs font-medium text-red-600 hover:text-red-500"
            >
                {t('Reset')}
            </button>
        </div>
    );
}

function ColumnToggleMenu({
    columns,
    hiddenSlugs,
    onToggle,
}: {
    columns: ColumnNode[];
    hiddenSlugs: string[];
    onToggle: (slug: string) => void;
}) {
    const t = useT();
    const toggleable = columns.filter((column) => column.allowUserHidden !== false);

    if (toggleable.length === 0) {
        return null;
    }

    return (
        <div className="absolute right-0 z-50 mt-2 w-52 rounded-lg border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] p-2 shadow-lg">
            <p className="px-2 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--color-orbit-nav-group-fg,#94a3b8)]">
                {t('Columns')}
            </p>
            <ul className="max-h-64 space-y-0.5 overflow-y-auto">
                {toggleable.map((column) => {
                    const hidden = hiddenSlugs.includes(column.slug);

                    return (
                        <li key={column.slug}>
                            <label className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-[color-mix(in_srgb,var(--color-orbit-nav-muted,#f8fafc)_55%,transparent)]">
                                <input
                                    type="checkbox"
                                    checked={!hidden}
                                    onChange={() => onToggle(column.slug)}
                                    className="rounded border-gray-300 text-orbit-primary-600 focus:ring-orbit-primary-500/30"
                                />
                                <span>{column.title}</span>
                            </label>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

export function EntityTableToolbar({
    title,
    columns,
    filterFields,
    inlineFilterFields = [],
    searchColumn,
    hiddenSlugs,
    onToggleColumn,
}: {
    title?: string | null;
    columns: ColumnNode[];
    filterFields: FieldNode[];
    inlineFilterFields?: FieldNode[];
    searchColumn?: string | null;
    hiddenSlugs: string[];
    onToggleColumn: (slug: string) => void;
}) {
    const t = useT();
    const [search, setSearch] = useState(() => (searchColumn ? readSearchParam(searchColumn) : ''));
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [columnsOpen, setColumnsOpen] = useState(false);
    const skipSearchEffect = useRef(true);
    const activeFilterCount = useMemo(
        () => countActiveFilters([...filterFields, ...inlineFilterFields]),
        [filterFields, inlineFilterFields],
    );

    useEffect(() => {
        if (!searchColumn) {
            return;
        }

        if (skipSearchEffect.current) {
            skipSearchEffect.current = false;

            return;
        }

        const handle = window.setTimeout(() => {
            visitTableGet((params) => {
                if (search.trim() === '') {
                    params.delete(`filter[${searchColumn}]`);
                } else {
                    params.set(`filter[${searchColumn}]`, search.trim());
                }

                params.delete('page');
            });
        }, 350);

        return () => window.clearTimeout(handle);
    }, [search, searchColumn]);

    return (
        <div className="relative z-20 overflow-visible px-1 py-1">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
                {title ? (
                    <h3 className="shrink-0 text-sm font-semibold text-[var(--color-orbit-secondary,#0f172a)]">
                        {title}
                    </h3>
                ) : null}

                <div className="ml-auto flex min-w-0 flex-wrap items-center justify-end gap-2">
                    <InlineTableFilters fields={inlineFilterFields} />

                    {searchColumn ? (
                        <div className="relative min-w-[12rem] sm:max-w-xs">
                            <Icon
                                name="bs.search"
                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-orbit-nav-group-fg,#94a3b8)]"
                            />
                            <input
                                type="search"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={t('Search')}
                                className="w-full rounded-lg border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] py-2 pl-9 pr-3 text-sm text-[var(--color-orbit-secondary,#334155)] outline-none placeholder:text-[var(--color-orbit-nav-group-fg,#94a3b8)] focus:border-orbit-primary-500 focus:ring-2 focus:ring-orbit-primary-500/20"
                            />
                        </div>
                    ) : null}

                    {filterFields.length > 0 ? (
                        <div className="relative">
                            <UiButton
                                type="button"
                                variant="default"
                                size="sm"
                                icon="bs.funnel"
                                onClick={() => {
                                    setFiltersOpen((open) => !open);
                                    setColumnsOpen(false);
                                }}
                            >
                                {t('Filters')}
                                {activeFilterCount > 0 ? (
                                    <span className="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-orbit-primary-600 px-1 text-[10px] font-semibold text-white">
                                        {activeFilterCount}
                                    </span>
                                ) : null}
                            </UiButton>
                            {filtersOpen ? (
                                <div className="absolute right-0 z-50 mt-2">
                                    <FilterPopoverPanel
                                        fields={filterFields}
                                        onClose={() => setFiltersOpen(false)}
                                    />
                                </div>
                            ) : null}
                        </div>
                    ) : null}

                    <div className="relative">
                        <UiButton
                            type="button"
                            variant="default"
                            size="sm"
                            iconOnly
                            icon="bs.layout-three-columns"
                            aria-label={t('Toggle columns')}
                            onClick={() => {
                                setColumnsOpen((open) => !open);
                                setFiltersOpen(false);
                            }}
                        />
                        {columnsOpen ? (
                            <ColumnToggleMenu
                                columns={columns}
                                hiddenSlugs={hiddenSlugs}
                                onToggle={onToggleColumn}
                            />
                        ) : null}
                    </div>
                </div>
            </div>
        </div>
    );
}

function PaginationControl({
    href,
    disabled = false,
    active = false,
    children,
    className,
}: {
    href?: string | null;
    disabled?: boolean;
    active?: boolean;
    children: ReactNode;
    className?: string;
}) {
    const baseClass = cn(
        'inline-flex min-h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs font-medium transition',
        active
            ? 'border-orbit-primary-600 bg-orbit-primary-600 text-white'
            : 'border-[var(--color-orbit-panel-border,#e2e8f0)] text-[var(--color-orbit-secondary,#334155)] hover:bg-[color-mix(in_srgb,var(--color-orbit-nav-muted,#f8fafc)_55%,transparent)]',
        disabled && 'pointer-events-none opacity-40',
        className,
    );

    if (href && !disabled) {
        return (
            <Link href={href} preserveScroll className={baseClass}>
                {children}
            </Link>
        );
    }

    return <span className={baseClass}>{children}</span>;
}

function PaginationNav({
    paginator,
    style = 'full',
}: {
    paginator: Record<string, unknown>;
    style?: 'full' | 'simple';
}) {
    const t = useT();
    const current = Number(paginator.current_page ?? 1);
    const last = Number(paginator.last_page ?? 1);
    const prev = paginator.prev_page_url as string | null | undefined;
    const next = paginator.next_page_url as string | null | undefined;
    const first = paginator.first_page_url as string | null | undefined;
    const lastUrl = paginator.last_page_url as string | null | undefined;
    const links = (paginator.links as PaginationLinkItem[] | undefined) ?? [];

    if (last <= 1) {
        return null;
    }

    if (style === 'simple') {
        return (
            <nav className="flex flex-wrap items-center gap-1">
                <PaginationControl href={prev} disabled={!prev}>
                    {t('Previous')}
                </PaginationControl>
                <span className="px-2 text-xs font-medium text-[var(--color-orbit-secondary,#334155)]">
                    {current} / {last}
                </span>
                <PaginationControl href={next} disabled={!next}>
                    {t('Next')}
                </PaginationControl>
            </nav>
        );
    }

    return (
        <nav className="flex flex-wrap items-center gap-1">
            <PaginationControl href={first} disabled={current <= 1}>
                {t('First')}
            </PaginationControl>
            <PaginationControl href={prev} disabled={!prev}>
                {t('Previous')}
            </PaginationControl>
            {links.map((link, index) =>
                link.type === 'ellipsis' ? (
                    <span
                        key={`ellipsis-${index}`}
                        className="inline-flex min-h-8 min-w-8 items-center justify-center px-1 text-xs text-[var(--color-orbit-nav-group-fg,#94a3b8)]"
                    >
                        …
                    </span>
                ) : (
                    <PaginationControl key={link.page} href={link.url} active={link.active}>
                        {link.page}
                    </PaginationControl>
                ),
            )}
            <PaginationControl href={next} disabled={!next}>
                {t('Next')}
            </PaginationControl>
            <PaginationControl href={lastUrl} disabled={current >= last}>
                {t('Last')}
            </PaginationControl>
        </nav>
    );
}

export function TablePagination({
    paginator,
    perPageOptions = [10, 25, 50, 100],
    paginationStyle = 'full',
}: {
    paginator: Record<string, unknown>;
    perPageOptions?: number[];
    paginationStyle?: 'full' | 'simple';
}) {
    const t = useT();
    const last = Number(paginator.last_page ?? 1);
    const from = paginator.from as number | null | undefined;
    const to = paginator.to as number | null | undefined;
    const total = Number(paginator.total ?? 0);
    const perPage = Number(paginator.per_page ?? perPageOptions[0] ?? 25);
    const style = (paginator.style as 'full' | 'simple' | undefined) ?? paginationStyle;
    const options = [...new Set([...perPageOptions, perPage])].sort((a, b) => a - b);

    const visitWithPerPage = (nextPerPage: number) => {
        visitTableGet((params) => {
            params.set('per_page', String(nextPerPage));
            params.delete('page');
        }, { preserveState: true });
    };

    if (total === 0) {
        return null;
    }

    const summary =
        last > 1
            ? t(':total records, showing :from–:to', {
                  total: String(total),
                  from: String(from ?? 0),
                  to: String(to ?? 0),
              })
            : t('Total :total records', { total: String(total) });

    return (
        <div className="flex flex-col gap-3 px-1 py-2 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-[var(--color-orbit-nav-group-fg,#64748b)]">
                <span>{summary}</span>
                <label className="inline-flex items-center gap-2 text-xs">
                    <span>{t('Rows per page')}</span>
                    <select
                        value={perPage}
                        onChange={(event) => visitWithPerPage(Number(event.target.value))}
                        className="rounded-md border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] px-2 py-1 text-sm text-[var(--color-orbit-secondary,#334155)] outline-none focus:border-orbit-primary-500 focus:ring-2 focus:ring-orbit-primary-500/20"
                    >
                        {options.map((option) => (
                            <option key={option} value={option}>
                                {option}
                            </option>
                        ))}
                    </select>
                </label>
            </div>
            <PaginationNav paginator={paginator} style={style} />
        </div>
    );
}

export function defaultHiddenColumnSlugs(columns: ColumnNode[]): string[] {
    return columns.filter((column) => column.defaultHidden).map((column) => column.slug);
}

export { asFields as tableFilterFields, appendFilterFieldToParams, visitTableGet };

function asFields(value: unknown): FieldNode[] {
    return Array.isArray(value) ? (value as FieldNode[]) : [];
}

function selectOptions(column: ColumnNode): Array<{ value: string; label: string }> {
    const options = column.filter?.attributes?.options;

    if (!options || typeof options !== 'object') {
        return [];
    }

    return Object.entries(options as Record<string, string>).map(([value, label]) => ({
        value,
        label: String(label),
    }));
}

function currentQueryFilter(column: string, pageUrl?: string): string {
    const search =
        pageUrl && pageUrl.includes('?')
            ? pageUrl.split('?')[1]
            : typeof window !== 'undefined'
              ? window.location.search.slice(1)
              : '';

    if (search === '') {
        return '';
    }

    const params = new URLSearchParams(search);
    const key = `filter[${column}]`;
    const single = params.get(key);

    if (single !== null && single !== '') {
        return single;
    }

    const arrayValues = params.getAll(`${key}[]`);

    return arrayValues.length === 1 ? arrayValues[0] : '';
}

function clearTabFilterParam(params: URLSearchParams, column: string): void {
    const key = `filter[${column}]`;

    params.delete(key);
    params.delete(`${key}[]`);
}

function visitWithParams(
    mutator: (params: URLSearchParams) => void,
    options: NonNullable<Parameters<typeof router.get>[2]> = {},
) {
    visitTableGet(mutator, options);
}

export function TableFilterTabs({ column, total }: { column: ColumnNode; total?: number }) {
    const t = useT();
    const { url } = usePage();
    const [active, setActive] = useState(() => currentQueryFilter(column.column, url));
    const options = selectOptions(column);

    useEffect(() => {
        setActive(currentQueryFilter(column.column, url));
    }, [column.column, url]);

    if (options.length === 0) {
        return null;
    }

    const tabs = [{ value: '', label: t('All') }, ...options];

    const selectTab = (value: string) => {
        setActive(value);

        visitWithParams((params) => {
            clearTabFilterParam(params, column.column);

            if (value !== '') {
                params.set(`filter[${column.column}]`, value);
            }

            params.delete('page');
        });
    };

    return (
        <div className="px-1 py-1">
            <div className="inline-flex max-w-full flex-wrap items-center gap-0.5 rounded-xl border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] p-1">
                {tabs.map((tab) => {
                    const selected = active === tab.value;

                    return (
                        <button
                            key={tab.value || 'all'}
                            type="button"
                            onClick={() => selectTab(tab.value)}
                            className={cn(
                                'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                selected
                                    ? 'bg-[var(--color-orbit-nav-muted,#f1f5f9)] text-[var(--color-orbit-secondary,#0f172a)]'
                                    : 'text-[var(--color-orbit-nav-group-fg,#64748b)] hover:bg-[var(--color-orbit-nav-muted,#f8fafc)] hover:text-[var(--color-orbit-secondary,#334155)]',
                            )}
                        >
                            {tab.label}
                            {selected && total != null ? (
                                <Badge size="sm" color="gray" className="min-w-[1.25rem] justify-center tabular-nums">
                                    {total}
                                </Badge>
                            ) : null}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
