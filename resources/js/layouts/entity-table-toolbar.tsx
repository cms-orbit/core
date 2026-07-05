import { Link, router, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
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

/** Read multiselect filter values from the current location (URL is the source of truth). */
function readInlineFilterFromLocation(name: string | null | undefined): string[] {
    if (!name || typeof window === 'undefined') {
        return [];
    }

    const params = new URLSearchParams(window.location.search);
    const scalar = params.get(name);

    if (scalar !== null && scalar !== '') {
        return scalar.includes(',')
            ? uniqueFilterValues(scalar.split(',').map((item) => item.trim()))
            : [scalar];
    }

    return readLegacyIndexedFilterParams(params, name);
}

/** Backwards compatibility for older indexed filter URLs. */
function readLegacyIndexedFilterParams(params: URLSearchParams, name: string): string[] {
    const escaped = escapeRegExp(name);
    const indexedPattern = new RegExp(`^${escaped}\\[(\\d+)]$`);
    const indexed: Array<{ index: number; value: string }> = [];

    params.forEach((value, key) => {
        if (value === '') {
            return;
        }

        const indexedMatch = key.match(indexedPattern);

        if (indexedMatch) {
            indexed.push({ index: Number(indexedMatch[1]), value });

            return;
        }

        if (key === `${name}[]`) {
            indexed.push({ index: indexed.length, value });
        }
    });

    if (indexed.length > 0) {
        return uniqueFilterValues(
            indexed.sort((left, right) => left.index - right.index).map((item) => item.value),
        );
    }

    const bracketValues = params.getAll(`${name}[]`);

    if (bracketValues.length > 0) {
        return uniqueFilterValues(bracketValues);
    }

    return [];
}

function copyParamsExceptKeys(source: URLSearchParams, excludedKeys: (name: string) => boolean): URLSearchParams {
    const next = new URLSearchParams();

    source.forEach((value, key) => {
        if (excludedKeys(key)) {
            return;
        }

        next.append(key, value);
    });

    return next;
}

function isFilterParamKey(key: string, filterName: string): boolean {
    return key === filterName || key.startsWith(`${filterName}[`);
}

function visitWithInlineFilter(
    filterName: string,
    values: string[],
    options: NonNullable<Parameters<typeof router.get>[2]> = {},
) {
    const next = copyParamsExceptKeys(new URLSearchParams(window.location.search), (key) =>
        isFilterParamKey(key, filterName),
    );

    const unique = uniqueFilterValues(values);

    if (unique.length > 0) {
        next.set(filterName, unique.join(','));
    }

    next.delete('page');

    const query = next.toString();

    router.get(query ? `${window.location.pathname}?${query}` : window.location.pathname, {}, {
        preserveScroll: true,
        preserveState: true,
        ...options,
    });
}

function hasFilterValue(params: URLSearchParams, name: string): boolean {
    const scalar = params.get(name);

    if (scalar !== null && scalar !== '') {
        return true;
    }

    return readLegacyIndexedFilterParams(params, name).length > 0;
}

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
    const placeholder =
        (field.attributes?.placeholder as string | undefined) ??
        (field.attributes?.title as string | undefined) ??
        '';
    const [selected, setSelected] = useState<string[]>(() => uniqueFilterValues(toSelectedValues(field.value)));

    useEffect(() => {
        if (!fieldName) {
            return;
        }

        const syncFromLocation = () => {
            setSelected(readInlineFilterFromLocation(fieldName));
        };

        window.addEventListener('popstate', syncFromLocation);

        return () => {
            window.removeEventListener('popstate', syncFromLocation);
        };
    }, [fieldName]);

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

function FilterPopoverForm({
    fields,
    onClose,
}: {
    fields: FieldNode[];
    onClose: () => void;
}) {
    const t = useT();
    const form = useOrbitForm();

    const apply = () => {
        form.submit('get', window.location.pathname);
        onClose();
    };

    const reset = () => {
        router.get(window.location.pathname);
        onClose();
    };

    return (
        <div className="space-y-3">
            {fields.map((field, index) => (
                <FieldRenderer key={field.name ?? `${field.component}-${index}`} node={field} data={{}} />
            ))}
            <div className="flex items-center gap-2 border-t border-[var(--color-orbit-panel-border,#e2e8f0)] pt-3">
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
            const params = new URLSearchParams(window.location.search);

            if (search.trim() === '') {
                params.delete(`filter[${searchColumn}]`);
            } else {
                params.set(`filter[${searchColumn}]`, search.trim());
            }

            params.delete('page');

            const query = params.toString();

            router.get(query ? `${window.location.pathname}?${query}` : window.location.pathname, {}, {
                preserveScroll: true,
                preserveState: true,
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
                                <div className="absolute right-0 z-50 mt-2 w-[min(100vw-2rem,22rem)] rounded-lg border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] p-4 shadow-lg">
                                    <div className="mb-3 flex items-center justify-between">
                                        <p className="text-sm font-semibold text-[var(--color-orbit-secondary,#334155)]">
                                            {t('Filters')}
                                        </p>
                                        <button
                                            type="button"
                                            className="text-xs text-[var(--color-orbit-nav-group-fg,#94a3b8)] hover:text-[var(--color-orbit-secondary,#64748b)]"
                                            onClick={() => setFiltersOpen(false)}
                                        >
                                            {t('Close')}
                                        </button>
                                    </div>
                                    <FormProvider initialData={{}} state={null}>
                                        <FilterPopoverForm
                                            fields={filterFields}
                                            onClose={() => setFiltersOpen(false)}
                                        />
                                    </FormProvider>
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
        const params = new URLSearchParams(window.location.search);
        params.set('per_page', String(nextPerPage));
        params.delete('page');

        const query = params.toString();

        router.get(query ? `${window.location.pathname}?${query}` : window.location.pathname, {}, {
            preserveScroll: true,
            preserveState: true,
        });
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

export { asFields as tableFilterFields };

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
    const params = new URLSearchParams(window.location.search);
    mutator(params);
    const query = params.toString();

    router.get(query ? `${window.location.pathname}?${query}` : window.location.pathname, {}, {
        preserveScroll: true,
        preserveState: true,
        ...options,
    });
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
