import type { ReactNode } from 'react';
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
    children: ReactNode;
}) {
    const hasError = Boolean(error);

    return (
        <div className={cn('mb-4', className)}>
            {title || hint ? (
                <div className="mb-1.5 flex items-center justify-between gap-2">
                    {title ? (
                        <label
                            htmlFor={htmlFor}
                            className={cn(
                                'block text-sm font-medium',
                                hasError ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-200',
                            )}
                        >
                            {title}
                            {required ? <span className="ml-0.5 text-red-500">*</span> : null}
                        </label>
                    ) : (
                        <span />
                    )}
                    {hint ? <span className="text-xs text-gray-400">{hint}</span> : null}
                </div>
            ) : null}
            {children}
            {help && !hasError ? (
                <p className="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{help}</p>
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
    'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-70 dark:bg-gray-900 dark:text-gray-100 dark:disabled:bg-white/5';

const inputNeutral =
    'border-gray-300 focus:border-orbit-primary-500 focus:ring-orbit-primary-500/30 dark:border-white/10';

const inputInvalid =
    'border-red-400 focus:border-red-500 focus:ring-red-500/30 dark:border-red-500/50';

/** Base input styling (neutral state). Kept for backwards compatibility. */
export const inputClass = cn(inputBase, inputNeutral);

/** Resolve input classes, switching to the invalid palette when `hasError`. */
export function fieldInputClass(hasError?: boolean): string {
    return cn(inputBase, hasError ? inputInvalid : inputNeutral);
}
