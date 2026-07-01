import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div
            className={cn(
                'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function CardHeader({ title, children }: { title?: string | null; children?: ReactNode }) {
    if (!title && !children) {
        return null;
    }

    return (
        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-3 dark:border-gray-700">
            {title ? (
                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">{title}</h3>
            ) : (
                <span />
            )}
            {children}
        </div>
    );
}

export function CardBody({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('p-5', className)}>{children}</div>;
}
