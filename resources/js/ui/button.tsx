import type { ButtonHTMLAttributes } from 'react';
import { cn } from '../lib/cn';

type Variant = 'primary' | 'default' | 'danger' | 'link';

const variants: Record<Variant, string> = {
    primary:
        'bg-orbit-primary text-white hover:opacity-90 focus-visible:ring-orbit-primary/40',
    default:
        'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700 dark:hover:bg-gray-700',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500/40',
    link: 'text-orbit-primary hover:underline',
};

export interface UiButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
}

export function UiButton({ variant = 'default', className, ...props }: UiButtonProps) {
    return (
        <button
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-md px-3.5 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}
