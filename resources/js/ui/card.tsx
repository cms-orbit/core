import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div
            className={cn(
                'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function CardHeader({
    title,
    description,
    children,
}: {
    title?: string | null;
    description?: string | null;
    children?: ReactNode;
}) {
    if (!title && !description && !children) {
        return null;
    }

    return (
        <div className="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-3.5 dark:border-white/10">
            <div className="min-w-0">
                {title ? (
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">{title}</h3>
                ) : null}
                {description ? (
                    <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{description}</p>
                ) : null}
            </div>
            {children ? <div className="shrink-0">{children}</div> : null}
        </div>
    );
}

export function CardBody({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('p-5', className)}>{children}</div>;
}

export function CardFooter({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div className={cn('border-t border-gray-100 px-5 py-3 dark:border-white/10', className)}>{children}</div>
    );
}
