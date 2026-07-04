import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div
            className={cn(
                'rounded-lg border',
                className,
            )}
            style={{
                backgroundColor: 'var(--color-orbit-panel-bg, #ffffff)',
                borderColor: 'var(--color-orbit-panel-border, #e2e8f0)',
            }}
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
        <div
            className="flex items-start justify-between gap-3 border-b px-5 py-3.5"
            style={{ borderColor: 'var(--color-orbit-panel-border, #e2e8f0)' }}
        >
            <div className="min-w-0">
                {title ? (
                    <h3 className="text-sm font-semibold" style={{ color: 'var(--color-orbit-secondary, #0f172a)' }}>
                        {title}
                    </h3>
                ) : null}
                {description ? (
                    <p className="mt-0.5 text-sm" style={{ color: 'var(--color-orbit-secondary, #64748b)' }}>
                        {description}
                    </p>
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
        <div
            className={cn('border-t px-5 py-3', className)}
            style={{ borderColor: 'var(--color-orbit-panel-border, #e2e8f0)' }}
        >
            {children}
        </div>
    );
}
