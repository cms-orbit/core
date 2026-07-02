import type { ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

export interface EmptyStateProps {
    icon?: string;
    heading?: ReactNode;
    description?: ReactNode;
    actions?: ReactNode;
    className?: string;
}

/** Centered placeholder for empty tables/lists — models Filament's `fi-ta-empty-state`. */
export function EmptyState({
    icon = 'bs.inbox',
    heading,
    description,
    actions,
    className,
}: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center justify-center px-6 py-12 text-center', className)}>
            <span className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                <Icon name={icon} className="text-2xl" />
            </span>
            {heading ? (
                <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{heading}</p>
            ) : null}
            {description ? (
                <p className="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{description}</p>
            ) : null}
            {actions ? <div className="mt-4 flex items-center gap-2">{actions}</div> : null}
        </div>
    );
}

/** A pulsing skeleton line used while deferred props resolve. */
export function SkeletonLine({ className }: { className?: string }) {
    return <div className={cn('h-3 animate-pulse rounded bg-gray-200 dark:bg-white/10', className)} />;
}

/** A block of skeleton rows for deferred table/list placeholders. */
export function SkeletonRows({ rows = 4, className }: { rows?: number; className?: string }) {
    return (
        <div className={cn('space-y-3', className)}>
            {Array.from({ length: rows }).map((_, index) => (
                <div key={index} className="flex items-center gap-3">
                    <div className="h-9 w-9 shrink-0 animate-pulse rounded-full bg-gray-200 dark:bg-white/10" />
                    <div className="flex-1 space-y-2">
                        <SkeletonLine className="w-1/3" />
                        <SkeletonLine className="w-2/3" />
                    </div>
                </div>
            ))}
        </div>
    );
}
