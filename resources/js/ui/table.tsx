import type { CSSProperties, ReactNode, ThHTMLAttributes, TdHTMLAttributes } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

const rowDividerClass =
    '[&>tr:not(:last-child)>td]:border-b [&>tr:not(:last-child)>td]:border-[var(--color-orbit-table-row-border,#f1f5f9)]';

/** Scrollable table wrapper; parent should use `overflow-hidden rounded-*` for clean corners. */
export function Table({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div className="overflow-x-auto rounded-[inherit]">
            <table className={cn('min-w-full border-separate border-spacing-0 text-sm', className)}>{children}</table>
        </div>
    );
}

export function TableHead({ children }: { children: ReactNode }) {
    return (
        <thead className="border-b border-[var(--color-orbit-panel-border,#e2e8f0)]">
            {children}
        </thead>
    );
}

export function TableBody({ children }: { children: ReactNode }) {
    return (
        <tbody
            className={cn('bg-[var(--color-orbit-panel-bg,#ffffff)]', rowDividerClass)}
        >
            {children}
        </tbody>
    );
}

export function TableRow({
    className,
    interactive = true,
    children,
    ...props
}: {
    className?: string;
    interactive?: boolean;
    children: ReactNode;
} & React.HTMLAttributes<HTMLTableRowElement>) {
    return (
        <tr
            className={cn(
                interactive && 'transition-colors hover:bg-[color-mix(in_srgb,var(--color-orbit-nav-muted,#f8fafc)_55%,transparent)]',
                className,
            )}
            {...props}
        >
            {children}
        </tr>
    );
}

type SortDirection = 'asc' | 'desc' | null;

export interface HeaderCellProps extends ThHTMLAttributes<HTMLTableCellElement> {
    sortable?: boolean;
    sortDirection?: SortDirection;
    onSort?: () => void;
    align?: 'left' | 'center' | 'right';
    children?: ReactNode;
}

export function TableHeaderCell({
    sortable = false,
    sortDirection = null,
    onSort,
    align = 'left',
    className,
    children,
    ...props
}: HeaderCellProps) {
    const alignClass = align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : 'text-left';

    return (
        <th
            className={cn(
                'px-4 py-2.5 text-xs font-medium first:pl-5 last:pr-5',
                alignClass,
                className,
            )}
            style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}
            {...props}
        >
            {sortable ? (
                <button
                    type="button"
                    onClick={onSort}
                    className="inline-flex items-center gap-1 hover:text-[var(--color-orbit-secondary,#334155)]"
                >
                    {children}
                    <Icon
                        name={sortDirection === 'asc' ? 'bs.sort-up' : sortDirection === 'desc' ? 'bs.sort-down' : 'bs.arrow-down-up'}
                        className={cn('text-sm', sortDirection ? 'text-orbit-primary-600' : 'opacity-40')}
                    />
                </button>
            ) : (
                children
            )}
        </th>
    );
}

export interface CellProps extends TdHTMLAttributes<HTMLTableCellElement> {
    align?: 'left' | 'center' | 'right';
    children?: ReactNode;
}

export function TableCell({ align = 'left', className, children, ...props }: CellProps) {
    const alignClass = align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : 'text-left';

    return (
        <td
            className={cn('px-4 py-2.5 first:pl-5 last:pr-5', alignClass, className)}
            style={{ color: 'var(--color-orbit-secondary, #334155)' }}
            {...props}
        >
            {children}
        </td>
    );
}
