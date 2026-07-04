import { useState } from 'react';
import type { ColumnNode, FieldNode, LayoutComponentProps } from './contract';
import { ChartLayout } from './layouts/chart';
import { ListenerLayout } from './layouts/listener';
import { LocaleTabsLayout } from './layouts/locale-tabs';
import { ModalLayout } from './layouts/modal';
import { SelectionLayout } from './layouts/selection';
import { DesignSettingsLayout } from './layouts/design-settings';
import { SeoSettingsLayout } from './layouts/seo-settings';
import { SettingsHubLayout } from './layouts/settings-hub';
import { SortableLayout } from './layouts/sortable';
import { cn } from './lib/cn';
import { useT } from './lib/i18n';
import type { LayoutComponent } from './registry';
import {
    ActionBar,
    FieldRenderer,
    LayoutChildren,
    LayoutNodeRenderer,
} from './screen-renderer';
import { Card, CardBody, CardHeader } from './ui/card';
import { EmptyState, SkeletonRows } from './ui/empty-state';
import { Table, TableBody, TableCell, TableHead, TableHeaderCell, TableRow } from './ui/table';

function asFields(value: unknown): FieldNode[] {
    return Array.isArray(value) ? (value as FieldNode[]) : [];
}

function asColumns(value: unknown): ColumnNode[] {
    return Array.isArray(value) ? (value as ColumnNode[]) : [];
}

function getByPath(data: Record<string, unknown>, path: string | undefined): unknown {
    if (!path) {
        return undefined;
    }

    return path.split('.').reduce<unknown>((current, segment) => {
        if (!current || typeof current !== 'object') {
            return undefined;
        }

        return (current as Record<string, unknown>)[segment];
    }, data);
}

/** Resolve a row collection from a repository target (array or paginator). */
function asRows(content: unknown): Record<string, unknown>[] {
    if (Array.isArray(content)) {
        return content as Record<string, unknown>[];
    }

    if (content && typeof content === 'object' && Array.isArray((content as { data?: unknown }).data)) {
        return (content as { data: Record<string, unknown>[] }).data;
    }

    return [];
}

function columnAlign(align?: ColumnNode['align']): 'left' | 'center' | 'right' {
    if (align === 'center') {
        return 'center';
    }

    if (align === 'end') {
        return 'right';
    }

    return 'left';
}

function renderedCell(row: Record<string, unknown>, column: ColumnNode): string | null {
    const cell = cellPayload(row, column);

    return typeof cell?.rendered === 'string' ? cell.rendered : null;
}

function fieldCell(row: Record<string, unknown>, column: ColumnNode): FieldNode | null {
    const cell = cellPayload(row, column);

    return cell?.field && typeof cell.field === 'object' ? (cell.field as FieldNode) : null;
}

function actionCell(row: Record<string, unknown>, column: ColumnNode): FieldNode[] {
    const cell = cellPayload(row, column);

    return Array.isArray(cell?.actions) ? (cell.actions as FieldNode[]) : [];
}

function cellPayload(row: Record<string, unknown>, column: ColumnNode): {
    rendered?: unknown;
    field?: unknown;
    actions?: unknown;
} | null {
    const cells = row._cells;

    if (!cells || typeof cells !== 'object') {
        return null;
    }

    const cell = (cells as Record<string, unknown>)[column.slug];

    return cell && typeof cell === 'object'
        ? (cell as { rendered?: unknown; field?: unknown; actions?: unknown })
        : null;
}

export function RowsLayout({ node, data, screen }: LayoutComponentProps) {
    const fields = asFields(node.data.fields);
    const title = node.data.title as string | null;

    return (
        <Card>
            <CardHeader title={title} />
            <CardBody>
                {fields.map((field, index) => (
                    <FieldRenderer
                        key={field.name ?? `${field.component}-${index}`}
                        node={field}
                        data={data}
                        screen={screen}
                    />
                ))}
            </CardBody>
        </Card>
    );
}

export function ColumnsLayout({ node, data, screen }: LayoutComponentProps) {
    return (
        <LayoutChildren
            nodes={node.children}
            data={data}
            screen={screen}
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
        />
    );
}

export function SplitLayout({ node, data, screen }: LayoutComponentProps) {
    return (
        <LayoutChildren
            nodes={node.children}
            data={data}
            screen={screen}
            className="grid grid-cols-1 gap-4 lg:grid-cols-3"
        />
    );
}

export function StackLayout({ node, data, screen }: LayoutComponentProps) {
    return <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />;
}

export function TabsLayout({ node, data, screen }: LayoutComponentProps) {
    const titles = (node.data.titles as (string | null)[] | undefined) ?? [];
    const [active, setActive] = useState(0);
    const panes = node.children;

    return (
        <Card>
            <div className="flex gap-1 overflow-x-auto border-b border-gray-100 px-3 dark:border-white/10">
                {panes.map((pane, index) => (
                    <button
                        key={pane.key}
                        type="button"
                        onClick={() => setActive(index)}
                        className={cn(
                            'whitespace-nowrap border-b-2 px-3 py-2.5 text-sm transition',
                            index === active
                                ? 'border-orbit-primary-600 font-medium text-orbit-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                        )}
                    >
                        {titles[index] ?? (pane.data.title as string | undefined) ?? `Tab ${index + 1}`}
                    </button>
                ))}
            </div>
            <CardBody>
                {panes[active] ? (
                    <LayoutNodeRenderer node={panes[active]} data={data} screen={screen} />
                ) : null}
            </CardBody>
        </Card>
    );
}

export function TabPaneLayout({ node, data, screen }: LayoutComponentProps) {
    return <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />;
}

function cellValue(row: Record<string, unknown>, column: ColumnNode): unknown {
    return row[column.name];
}

export function TableLayout({ node, data, screen }: LayoutComponentProps) {
    const t = useT();
    const columns = asColumns(node.data.columns);
    const target = node.data.target as string | undefined;
    const hasTarget = target != null;
    const raw = hasTarget ? getByPath(data, target) : undefined;
    /** A missing target key means the prop is still deferred (Inertia v3). */
    const hasSerializedRows = Array.isArray(node.data.rows);
    const isDeferred = hasTarget && raw === undefined && !hasSerializedRows;
    const rows = asRows(node.data.rows ?? raw);

    return (
        <Card>
            <CardHeader title={node.data.title as string | null} />
            {isDeferred ? (
                <CardBody>
                    <SkeletonRows rows={5} />
                </CardBody>
            ) : rows.length === 0 ? (
                <EmptyState
                    heading={(node.data.textNotFound as string) ?? t('No records found')}
                    description={node.data.textNotFoundSubtitle as string | undefined}
                />
            ) : (
                <Table>
                    <TableHead>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHeaderCell key={column.slug} align={columnAlign(column.align)}>
                                    {column.title}
                                </TableHeaderCell>
                            ))}
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {rows.map((row, rowIndex) => (
                            <TableRow key={String(row.id ?? row.key ?? rowIndex)}>
                                {columns.map((column) => (
                                    <TableCell key={column.slug} align={columnAlign(column.align)}>
                                        {actionCell(row, column).length > 0 ? (
                                            <ActionBar actions={actionCell(row, column)} data={row} screen={screen} />
                                        ) : fieldCell(row, column) != null ? (
                                            <FieldRenderer node={fieldCell(row, column)!} data={row} screen={screen} />
                                        ) : renderedCell(row, column) != null ? (
                                            <span dangerouslySetInnerHTML={{ __html: renderedCell(row, column) ?? '' }} />
                                        ) : (
                                            String(cellValue(row, column) ?? '')
                                        )}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </Card>
    );
}

export function LegendLayout({ node, data }: LayoutComponentProps) {
    const columns = asColumns(node.data.columns);
    const target = node.data.target as string | undefined;
    const source = (target ? getByPath(data, target) : data) as Record<string, unknown>;

    return (
        <Card>
            <CardHeader title={node.data.title as string | null} />
            <CardBody>
                <dl className="divide-y divide-gray-100 dark:divide-gray-800">
                    {columns.map((column) => (
                        <div key={column.slug} className="grid grid-cols-3 gap-4 py-2">
                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {column.title}
                            </dt>
                            <dd className="col-span-2 text-sm text-gray-900 dark:text-gray-100">
                                {column.rendered != null ? (
                                    <span dangerouslySetInnerHTML={{ __html: column.rendered }} />
                                ) : (
                                    String(source?.[column.name] ?? '')
                                )}
                            </dd>
                        </div>
                    ))}
                </dl>
            </CardBody>
        </Card>
    );
}

interface MetricEntry {
    label?: string;
    value?: string | number;
    diff?: number;
}

export function MetricLayout({ node }: LayoutComponentProps) {
    const metrics = (node.data.metrics as MetricEntry[] | undefined) ?? [];
    const title = node.data.title as string | null;

    if (metrics.length === 0) {
        return null;
    }

    return (
        <div className="space-y-2">
            {title ? <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-200">{title}</h3> : null}
            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                {metrics.map((metric, index) => (
                    <Card key={metric.label ?? index}>
                        <CardBody>
                            <p className="text-xs uppercase tracking-wide text-gray-400">{metric.label}</p>
                            <p className="mt-1 text-2xl font-semibold">{metric.value ?? '—'}</p>
                            {typeof metric.diff === 'number' ? (
                                <p className={cn('mt-1 text-xs', metric.diff >= 0 ? 'text-green-600' : 'text-red-600')}>
                                    {metric.diff >= 0 ? '▲' : '▼'} {Math.abs(metric.diff)}%
                                </p>
                            ) : null}
                        </CardBody>
                    </Card>
                ))}
            </div>
        </div>
    );
}

export function PersonaLayout({ node }: LayoutComponentProps) {
    const title = node.data.title as string | null;
    const subTitle = node.data.subTitle as string | null;
    const image = node.data.image as string | null;
    const url = node.data.url as string | null;

    const content = (
        <div className="flex items-center gap-3">
            {image ? (
                <img src={image} alt={title ?? ''} className="h-10 w-10 rounded-full object-cover" />
            ) : (
                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-orbit-primary/10 text-sm font-semibold text-orbit-primary">
                    {(title ?? '?').slice(0, 1).toUpperCase()}
                </span>
            )}
            <div className="min-w-0">
                <p className="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{title}</p>
                {subTitle ? <p className="truncate text-xs text-gray-500">{subTitle}</p> : null}
            </div>
        </div>
    );

    return url ? (
        <a href={url} className="block">
            {content}
        </a>
    ) : (
        content
    );
}

export function FacepileLayout({ node }: LayoutComponentProps) {
    const images = (node.data.images as string[] | undefined) ?? [];

    return (
        <div className="flex -space-x-2">
            {images.map((image, index) => (
                <img
                    key={index}
                    src={image}
                    alt=""
                    className="h-8 w-8 rounded-full border-2 border-white object-cover dark:border-gray-800"
                />
            ))}
        </div>
    );
}

interface MenuEntry {
    label: string;
    url?: string;
    active?: boolean;
}

export function SideMenuLayout({ node }: LayoutComponentProps) {
    const items = (node.data.items as MenuEntry[] | undefined) ?? [];

    return (
        <nav className="space-y-0.5">
            {items.map((item, index) => (
                <a
                    key={item.url ?? index}
                    href={item.url ?? '#'}
                    className={cn(
                        'block rounded-md px-3 py-2 text-sm',
                        item.active
                            ? 'bg-orbit-primary/10 font-medium text-orbit-primary'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                    )}
                >
                    {item.label}
                </a>
            ))}
        </nav>
    );
}

export function TabMenuLayout({ node }: LayoutComponentProps) {
    const items = (node.data.items as MenuEntry[] | undefined) ?? [];

    return (
        <nav className="flex gap-1 border-b border-gray-200 dark:border-gray-700">
            {items.map((item, index) => (
                <a
                    key={item.url ?? index}
                    href={item.url ?? '#'}
                    className={cn(
                        'px-3 py-2 text-sm',
                        item.active
                            ? 'border-b-2 border-orbit-primary font-medium text-orbit-primary'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400',
                    )}
                >
                    {item.label}
                </a>
            ))}
        </nav>
    );
}

export function BrowsingLayout({ node }: LayoutComponentProps) {
    const url = node.data.url as string | undefined;

    if (!url) {
        return null;
    }

    return (
        <Card>
            <iframe
                src={url}
                title={(node.data.title as string) ?? 'Browsing'}
                className="h-[60vh] w-full rounded-xl"
            />
        </Card>
    );
}

export function ContentLayout({ node, data, screen }: LayoutComponentProps) {
    return <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />;
}

export function AccordionLayout({ node, data, screen }: LayoutComponentProps) {
    const [openKeys, setOpenKeys] = useState<Record<string, boolean>>({});

    const toggle = (key: string) => {
        setOpenKeys((current) => ({ ...current, [key]: !current[key] }));
    };

    return (
        <Card>
            <div className="divide-y divide-gray-100 dark:divide-gray-800">
                {node.children.map((child, index) => {
                    const open = openKeys[child.key] ?? index === 0;
                    const title = (child.data.title as string | undefined) ?? `Section ${index + 1}`;

                    return (
                        <div key={child.key}>
                            <button
                                type="button"
                                onClick={() => toggle(child.key)}
                                className="flex w-full items-center justify-between px-5 py-3 text-left text-sm font-medium text-gray-900 dark:text-gray-100"
                            >
                                {title}
                                <span className="text-gray-400">{open ? '−' : '+'}</span>
                            </button>
                            {open ? (
                                <div className="px-5 pb-4">
                                    <LayoutNodeRenderer node={child} data={data} screen={screen} />
                                </div>
                            ) : null}
                        </div>
                    );
                })}
            </div>
        </Card>
    );
}

export function BlockLayout({ node, data, screen }: LayoutComponentProps) {
    const title = node.data.title as string | null;
    const description = node.data.description as string | null;

    return (
        <Card>
            {title || description ? (
                <div className="border-b border-gray-100 px-5 py-3 dark:border-gray-700">
                    {title ? (
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">{title}</h3>
                    ) : null}
                    {description ? (
                        <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{description}</p>
                    ) : null}
                </div>
            ) : null}
            <CardBody>
                <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />
            </CardBody>
        </Card>
    );
}

export function CardLayout({ node, data, screen }: LayoutComponentProps) {
    const title = node.data.title as string | null;
    const image = node.data.image as string | null;

    return (
        <Card>
            {image ? <img src={image} alt={title ?? ''} className="h-40 w-full rounded-t-xl object-cover" /> : null}
            <CardHeader title={title} />
            <CardBody>
                {node.data.description ? (
                    <p className="mb-3 text-sm text-gray-500 dark:text-gray-400">{String(node.data.description)}</p>
                ) : null}
                <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />
            </CardBody>
        </Card>
    );
}

export function ViewLayout({ node }: LayoutComponentProps) {
    const html = node.data.html as string | undefined;

    if (!html) {
        return null;
    }

    return <div className="orbit-view-layout" dangerouslySetInnerHTML={{ __html: html }} />;
}

/** All layout registry slots. */
export const layoutComponents: Record<string, LayoutComponent> = {
    rows: RowsLayout,
    columns: ColumnsLayout,
    split: SplitLayout,
    blank: StackLayout,
    wrapper: StackLayout,
    block: BlockLayout,
    accordion: AccordionLayout,
    tabs: TabsLayout,
    'tab-pane': TabPaneLayout,
    'locale-tabs': LocaleTabsLayout,
    table: TableLayout,
    legend: LegendLayout,
    modal: ModalLayout,
    selection: SelectionLayout,
    listener: ListenerLayout,
    metric: MetricLayout,
    chart: ChartLayout,
    sortable: SortableLayout,
    persona: PersonaLayout,
    facepile: FacepileLayout,
    'side-menu': SideMenuLayout,
    'tab-menu': TabMenuLayout,
    browsing: BrowsingLayout,
    content: ContentLayout,
    card: CardLayout,
    'settings-hub': SettingsHubLayout,
    'design-settings': DesignSettingsLayout,
    'seo-settings': SeoSettingsLayout,
    view: ViewLayout,
};
