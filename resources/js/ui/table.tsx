import type { ReactNode, ThHTMLAttributes, TdHTMLAttributes } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

/** Scrollable table wrapper with Filament-style header/row styling. */
export function Table({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div className="overflow-x-auto">
            <table className={cn('min-w-full divide-y divide-gray-200 dark:divide-white/10', className)}>
                {children}
            </table>
        </div>
    );
}

export function TableHead({ children }: { children: ReactNode }) {
    return <thead className="bg-gray-50 dark:bg-white/5">{children}</thead>;
}

export function TableBody({ children }: { children: ReactNode }) {
    return <tbody className="divide-y divide-gray-100 dark:divide-white/5">{children}</tbody>;
}

export function TableRow({
    className,
    interactive = false,
    children,
    ...props
}: {
    className?: string;
    interactive?: boolean;
    children: ReactNode;
} & React.HTMLAttributes<HTMLTableRowElement>) {
    return (
        <tr
            className={cn(interactive && 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5', className)}
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
                'px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400',
                alignClass,
                className,
            )}
            {...props}
        >
            {sortable ? (
                <button
                    type="button"
                    onClick={onSort}
                    className="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200"
                >
                    {children}
                    <Icon
                        name={sortDirection === 'asc' ? 'bs.sort-up' : sortDirection === 'desc' ? 'bs.sort-down' : 'bs.arrow-down-up'}
                        className={cn('text-sm', sortDirection ? 'text-orbit-primary-600' : 'text-gray-300')}
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
        <td className={cn('px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200', alignClass, className)} {...props}>
            {children}
        </td>
    );
}
