import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

type Variant = 'primary' | 'default' | 'danger' | 'success' | 'warning' | 'ghost' | 'link';
type Size = 'sm' | 'md' | 'lg';

const variants: Record<Variant, string> = {
    primary:
        'bg-orbit-primary-600 text-white shadow-sm hover:bg-orbit-primary-500 focus-visible:ring-orbit-primary-500/50',
    default:
        'bg-white text-gray-700 border border-gray-300 shadow-sm hover:bg-gray-50 focus-visible:ring-gray-400/40 dark:bg-white/5 dark:text-gray-100 dark:border-white/10 dark:hover:bg-white/10',
    danger: 'bg-red-600 text-white shadow-sm hover:bg-red-500 focus-visible:ring-red-500/50',
    success: 'bg-green-600 text-white shadow-sm hover:bg-green-500 focus-visible:ring-green-500/50',
    warning: 'bg-amber-500 text-white shadow-sm hover:bg-amber-400 focus-visible:ring-amber-500/50',
    ghost: 'text-gray-600 hover:bg-gray-100 focus-visible:ring-gray-400/40 dark:text-gray-300 dark:hover:bg-white/10',
    link: 'text-orbit-primary-600 hover:text-orbit-primary-500 hover:underline dark:text-orbit-primary-400',
};

const sizes: Record<Size, string> = {
    sm: 'gap-1.5 rounded-md px-2.5 py-1.5 text-xs',
    md: 'gap-2 rounded-lg px-3.5 py-2 text-sm',
    lg: 'gap-2 rounded-lg px-4 py-2.5 text-sm',
};

const iconSizes: Record<Size, string> = {
    sm: 'p-1.5 rounded-md',
    md: 'p-2 rounded-lg',
    lg: 'p-2.5 rounded-lg',
};

export interface UiButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Variant;
    size?: Size;
    /** Leading icon name (Iconify/bootstrap), e.g. `bs.plus`. */
    icon?: string;
    /** Trailing icon name. */
    trailingIcon?: string;
    /** Render as a square icon-only button. */
    iconOnly?: boolean;
    /** Show a spinner and disable interaction. */
    loading?: boolean;
    children?: ReactNode;
}

export function UiButton({
    variant = 'default',
    size = 'md',
    icon,
    trailingIcon,
    iconOnly = false,
    loading = false,
    disabled,
    className,
    children,
    ...props
}: UiButtonProps) {
    return (
        <button
            disabled={disabled || loading}
            className={cn(
                'inline-flex items-center justify-center font-medium transition focus:outline-none focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-50',
                iconOnly ? iconSizes[size] : sizes[size],
                variants[variant],
                className,
            )}
            {...props}
        >
            {loading ? (
                <Icon name="bs.arrow-repeat" className="animate-spin text-base" />
            ) : icon ? (
                <Icon name={icon} className="text-base" />
            ) : null}
            {iconOnly ? null : children}
            {!iconOnly && !loading && trailingIcon ? <Icon name={trailingIcon} className="text-base" /> : null}
        </button>
    );
}
