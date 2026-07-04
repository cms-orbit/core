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
                <span
                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                    style={{
                        backgroundColor: 'var(--color-orbit-nav-section-bg, #f1f5f9)',
                        color: 'var(--color-orbit-primary, #10b981)',
                    }}
                >
                    <Icon name={icon} className="text-lg" />
                </span>
            ) : null}
            <div className="min-w-0 flex-1">
                {heading ? (
                    <h3 className="text-sm font-semibold" style={{ color: 'var(--color-orbit-secondary, #0f172a)' }}>{heading}</h3>
                ) : null}
                {description ? (
                    <p className="mt-0.5 text-sm" style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}>{description}</p>
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
                'rounded-xl border shadow-sm',
                className,
            )}
            style={{
                backgroundColor: 'var(--color-orbit-panel-bg, #ffffff)',
                borderColor: 'var(--color-orbit-panel-border, #e2e8f0)',
            }}
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
                        <div className="border-t" style={{ borderColor: 'var(--color-orbit-panel-border, #e2e8f0)' }} />
                    ) : null}
                    {body}
                </>
            )}
            {footer ? (
                <div className="border-t px-5 py-3" style={{ borderColor: 'var(--color-orbit-panel-border, #e2e8f0)' }}>{footer}</div>
            ) : null}
        </section>
    );
}
