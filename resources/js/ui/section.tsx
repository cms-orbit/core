import { useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';
import { Icon } from './icon';

export interface SectionProps {
    heading?: ReactNode;
    description?: ReactNode;
    /** Leading glyph shown in a tinted circle. */
    icon?: string;
    /** Content shown on the right of the header (actions, badges). */
    headerEnd?: ReactNode;
    /**
     * Aside layout: header/description sit in a left column, content on the
     * right — matching Filament's `aside` sections.
     */
    aside?: boolean;
    collapsible?: boolean;
    defaultCollapsed?: boolean;
    footer?: ReactNode;
    className?: string;
    children: ReactNode;
}

/** Grouping container with header, description and optional collapse/aside — models Filament's `fi-section`. */
export function Section({
    heading,
    description,
    icon,
    headerEnd,
    aside = false,
    collapsible = false,
    defaultCollapsed = false,
    footer,
    className,
    children,
}: SectionProps) {
    const [collapsed, setCollapsed] = useState(defaultCollapsed);
    const hasHeader = Boolean(heading || description || icon || headerEnd);

    const header = hasHeader ? (
        <div
            className={cn(
                'flex items-start gap-3',
                collapsible && !aside && 'cursor-pointer select-none',
                aside ? 'md:w-72 md:shrink-0' : 'px-5 py-4',
            )}
            onClick={collapsible && !aside ? () => setCollapsed((value) => !value) : undefined}
        >
            {icon ? (
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orbit-primary-50 text-orbit-primary-600 dark:bg-orbit-primary-500/10 dark:text-orbit-primary-400">
                    <Icon name={icon} className="text-lg" />
                </span>
            ) : null}
            <div className="min-w-0 flex-1">
                {heading ? (
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">{heading}</h3>
                ) : null}
                {description ? (
                    <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{description}</p>
                ) : null}
            </div>
            {headerEnd ? <div className="shrink-0" onClick={(e) => e.stopPropagation()}>{headerEnd}</div> : null}
            {collapsible && !aside ? (
                <Icon
                    name="bs.chevron-down"
                    className={cn('mt-1 shrink-0 text-gray-400 transition-transform', collapsed && '-rotate-90')}
                />
            ) : null}
        </div>
    ) : null;

    const body =
        !collapsible || !collapsed ? (
            <div className={cn(aside ? 'min-w-0 flex-1' : 'px-5 pb-5', hasHeader && !aside ? 'pt-0' : '')}>
                {children}
            </div>
        ) : null;

    return (
        <section
            className={cn(
                'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900',
                className,
            )}
        >
            {aside ? (
                <div className="flex flex-col gap-4 p-5 md:flex-row md:gap-8">
                    {header}
                    {body}
                </div>
            ) : (
                <>
                    {header}
                    {hasHeader && body ? (
                        <div className="border-t border-gray-100 dark:border-white/10" />
                    ) : null}
                    {body}
                </>
            )}
            {footer ? (
                <div className="border-t border-gray-100 px-5 py-3 dark:border-white/10">{footer}</div>
            ) : null}
        </section>
    );
}
