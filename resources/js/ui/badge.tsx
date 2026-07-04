import type { ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

export type BadgeColor =
    | 'primary'
    | 'gray'
    | 'success'
    | 'warning'
    | 'danger'
    | 'info';

type Size = 'sm' | 'md';

const colors: Record<BadgeColor, string> = {
    primary: 'bg-orbit-primary-50 text-orbit-primary-700 ring-orbit-primary-600/20 dark:bg-orbit-primary-500/10 dark:text-orbit-primary-400 dark:ring-orbit-primary-400/30',
    gray: 'ring-[var(--color-orbit-nav-section-border,#cbd5e1)] bg-[var(--color-orbit-nav-section-bg,#f8fafc)] text-[var(--color-orbit-nav-section-fg,#475569)]',
    success: 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-400/30',
    warning: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/30',
    danger: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-400/30',
    info: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-400/30',
};

const sizes: Record<Size, string> = {
    sm: 'gap-1 px-1.5 py-0.5 text-[0.6875rem]',
    md: 'gap-1 px-2 py-0.5 text-xs',
};

export interface BadgeProps {
    color?: BadgeColor;
    size?: Size;
    icon?: string;
    className?: string;
    children: ReactNode;
}

/** Soft, pill-shaped status badge modelled on Filament's `fi-badge`. */
export function Badge({ color = 'gray', size = 'md', icon, className, children }: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md font-medium ring-1 ring-inset',
                colors[color],
                sizes[size],
                className,
            )}
        >
            {icon ? <Icon name={icon} className="text-[0.875em]" /> : null}
            {children}
        </span>
    );
}
