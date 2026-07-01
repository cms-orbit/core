import { useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export type BannerTone = 'info' | 'success' | 'warning' | 'danger';

const toneStyle: Record<BannerTone, string> = {
    info: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200',
    success: 'border-green-200 bg-green-50 text-green-800 dark:border-green-500/40 dark:bg-green-500/10 dark:text-green-200',
    warning: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
    danger: 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200',
};

/** Inline page/panel banner for announcements and warnings. */
export function Banner({
    tone = 'info',
    title,
    dismissible = false,
    actions,
    children,
    className,
}: {
    tone?: BannerTone;
    title?: ReactNode;
    dismissible?: boolean;
    actions?: ReactNode;
    children?: ReactNode;
    className?: string;
}) {
    const [visible, setVisible] = useState(true);

    if (!visible) {
        return null;
    }

    return (
        <div className={cn('flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', toneStyle[tone], className)}>
            <div className="flex-1">
                {title ? <p className="font-semibold">{title}</p> : null}
                {children ? <div className={cn(title && 'mt-0.5')}>{children}</div> : null}
            </div>
            {actions ? <div className="flex shrink-0 items-center gap-2">{actions}</div> : null}
            {dismissible ? (
                <button
                    type="button"
                    onClick={() => setVisible(false)}
                    className="shrink-0 text-current/60 hover:text-current"
                    aria-label="Dismiss"
                >
                    &times;
                </button>
            ) : null}
        </div>
    );
}
