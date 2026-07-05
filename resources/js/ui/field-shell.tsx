import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

/** Standard vertical field wrapper: label, control slot, help text, error. */
export function FieldShell({
    title,
    help,
    required,
    error,
    htmlFor,
    hint,
    className,
    collapsible = false,
    defaultCollapsed = false,
    compact = false,
    children,
}: {
    title?: string | null;
    help?: string | null;
    required?: boolean;
    error?: string | null;
    htmlFor?: string;
    /** Small end-aligned text/action rendered beside the label. */
    hint?: ReactNode;
    className?: string;
    collapsible?: boolean;
    defaultCollapsed?: boolean;
    compact?: boolean;
    children: ReactNode;
}) {
    const hasError = Boolean(error);
    const [collapsed, setCollapsed] = useState(defaultCollapsed && !hasError);

    useEffect(() => {
        if (hasError) {
            setCollapsed(false);
        }
    }, [hasError]);

    return (
        <div className={cn(compact ? 'mb-0' : 'mb-4', className)}>
            {title || hint ? (
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    {collapsible ? (
                        <button
                            type="button"
                            onClick={() => setCollapsed((value) => !value)}
                            className="flex min-w-0 flex-1 items-center gap-2 text-left"
                        >
                            <Icon
                                name="bs.chevron-down"
                                className={cn('shrink-0 text-xs text-gray-400 transition-transform', collapsed && '-rotate-90')}
                            />
                            {title ? (
                                <span
                                    className={cn(
                                        'block text-sm font-medium',
                                        hasError ? 'text-red-600 dark:text-red-400' : 'text-[var(--color-orbit-secondary,#334155)]',
                                    )}
                                >
                                    {title}
                                    {required ? <span className="ml-0.5 text-red-500">*</span> : null}
                                </span>
                            ) : null}
                        </button>
                    ) : title ? (
                        <label
                            htmlFor={htmlFor}
                            className={cn(
                                'block text-sm font-medium',
                                hasError ? 'text-red-600 dark:text-red-400' : 'text-[var(--color-orbit-secondary,#334155)]',
                            )}
                        >
                            {title}
                            {required ? <span className="ml-0.5 text-red-500">*</span> : null}
                        </label>
                    ) : (
                        <span />
                    )}
                    {hint ? <span className="text-xs text-[var(--color-orbit-nav-group-fg,#64748b)]">{hint}</span> : null}
                </div>
            ) : null}
            {!collapsed ? children : null}
            {help && !hasError && !collapsed ? (
                <p className="mt-1.5 text-xs text-[var(--color-orbit-nav-group-fg,#64748b)]">{help}</p>
            ) : null}
            {hasError ? (
                <p className="mt-1.5 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                    <Icon name="bs.exclamation-circle" className="text-sm" />
                    {error}
                </p>
            ) : null}
        </div>
    );
}

const inputBase =
    'block w-full rounded-md border px-3 py-2 text-sm transition placeholder:text-[var(--color-orbit-nav-group-fg,#94a3b8)] focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-70';

const inputNeutral =
    'text-[var(--color-orbit-secondary,#0f172a)] bg-[var(--color-orbit-panel-bg,#ffffff)] border-[var(--color-orbit-panel-border,#e2e8f0)] focus:border-orbit-primary-500 focus:ring-orbit-primary-500/20';

const inputInvalid =
    'border-red-400 focus:border-red-500 focus:ring-red-500/20 dark:border-red-500/50';

/** Base input styling (neutral state). Kept for backwards compatibility. */
export const inputClass = cn(inputBase, inputNeutral);

/** Resolve input classes, switching to the invalid palette when `hasError`. */
export function fieldInputClass(hasError?: boolean): string {
    return cn(inputBase, hasError ? inputInvalid : inputNeutral);
}
