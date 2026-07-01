import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

/** Standard vertical field wrapper: label, control slot, help text, error. */
export function FieldShell({
    title,
    help,
    required,
    error,
    htmlFor,
    className,
    children,
}: {
    title?: string | null;
    help?: string | null;
    required?: boolean;
    error?: string | null;
    htmlFor?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('mb-4', className)}>
            {title ? (
                <label
                    htmlFor={htmlFor}
                    className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                >
                    {title}
                    {required ? <span className="ml-0.5 text-red-500">*</span> : null}
                </label>
            ) : null}
            {children}
            {help ? (
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{help}</p>
            ) : null}
            {error ? <p className="mt-1 text-xs text-red-600">{error}</p> : null}
        </div>
    );
}

export const inputClass =
    'block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-orbit-primary focus:outline-none focus:ring-1 focus:ring-orbit-primary dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
