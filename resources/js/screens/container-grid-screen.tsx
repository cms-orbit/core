import { Link } from '@inertiajs/react';
import { Card, CardBody, CardFooter } from '../ui/card';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { Icon } from '../ui/icon';
import type { CustomComponentProps } from '../contract';

interface ContainerCard {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    icon?: string | null;
    color?: string | null;
    isolationLabel: string;
    routingSupports: string[];
    lifecycleLabel: string;
    themeSelectable: boolean;
    instancesCount: number;
    viewUrl: string;
    instancesUrl: string;
}

interface ContainerPagination {
    data: ContainerCard[];
    current_page: number;
    last_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
    prev_page_url?: string | null;
    next_page_url?: string | null;
}

export function ContainerGridScreen({ data }: CustomComponentProps) {
    const t = useT();
    const paginator = data.containers as ContainerPagination | undefined;
    const cards = paginator?.data ?? [];

    if (cards.length === 0) {
        return (
            <Card>
                <CardBody className="space-y-2 py-10 text-center">
                    <p className="text-base font-semibold text-[var(--color-orbit-secondary,#0f172a)]">
                        {t('No containers found')}
                    </p>
                    <p className="text-sm text-[var(--color-orbit-secondary,#64748b)]">
                        {t('Connect a package or submit a container to see it here.')}
                    </p>
                </CardBody>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                {cards.map((container) => (
                    <ContainerCardView key={container.id} container={container} />
                ))}
            </div>

            {paginator && paginator.last_page > 1 ? (
                <div className="flex flex-col gap-3 rounded-xl border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-panel-bg,#ffffff)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-[var(--color-orbit-secondary,#64748b)]">
                        {t('Showing :from-:to of :total containers', {
                            from: paginator.from ?? 0,
                            to: paginator.to ?? 0,
                            total: paginator.total,
                        })}
                    </p>

                    <div className="flex items-center gap-2">
                        <PaginationLink href={paginator.prev_page_url} icon="bs.chevron-left">
                            {t('Previous')}
                        </PaginationLink>
                        <span className="text-sm text-[var(--color-orbit-secondary,#64748b)]">
                            {paginator.current_page} / {paginator.last_page}
                        </span>
                        <PaginationLink href={paginator.next_page_url} trailingIcon="bs.chevron-right">
                            {t('Next')}
                        </PaginationLink>
                    </div>
                </div>
            ) : null}
        </div>
    );
}

function ContainerCardView({ container }: { container: ContainerCard }) {
    const t = useT();
    const accent = container.color || '#10b981';

    return (
        <Card className="overflow-hidden border border-[var(--color-orbit-panel-border,#e2e8f0)] shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <div className="h-1.5 w-full" style={{ backgroundColor: accent }} />

            <CardBody className="space-y-5">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-3">
                        <div
                            className="flex size-12 shrink-0 items-center justify-center rounded-2xl border"
                            style={{
                                color: accent,
                                backgroundColor: `${accent}1A`,
                                borderColor: `${accent}33`,
                            }}
                        >
                            <Icon name={container.icon || 'bs.box'} className="text-xl" />
                        </div>

                        <div className="min-w-0 space-y-1">
                            <p className="truncate text-lg font-semibold text-[var(--color-orbit-secondary,#0f172a)]">
                                {container.name}
                            </p>
                            <p className="text-sm text-[var(--color-orbit-secondary,#64748b)]">{container.slug}</p>
                        </div>
                    </div>

                    <div
                        className="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                        style={{
                            color: accent,
                            backgroundColor: `${accent}14`,
                        }}
                    >
                        {container.instancesCount} {t('Instances')}
                    </div>
                </div>

                <p className="min-h-12 text-sm leading-6 text-[var(--color-orbit-secondary,#64748b)]">
                    {container.description || t('No description provided.')}
                </p>

                <dl className="grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                    <MetaItem label={t('Isolation')} value={container.isolationLabel} />
                    <MetaItem label={t('Lifecycle')} value={container.lifecycleLabel} />
                    <MetaItem
                        label={t('Selectable themes')}
                        value={container.themeSelectable ? t('Yes') : t('No')}
                    />
                </dl>

                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--color-orbit-secondary,#94a3b8)]">
                        {t('Routing Supports')}
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {container.routingSupports.map((support) => (
                            <span
                                key={support}
                                className="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium text-[var(--color-orbit-secondary,#475569)]"
                                style={{ borderColor: 'var(--color-orbit-panel-border,#e2e8f0)' }}
                            >
                                {support}
                            </span>
                        ))}
                    </div>
                </div>
            </CardBody>

            <CardFooter className="flex items-center justify-between gap-3">
                <Link
                    href={container.viewUrl}
                    className="text-sm font-medium text-[var(--color-orbit-secondary,#475569)] hover:underline"
                >
                    {t('View details')}
                </Link>

                <Link
                    href={container.instancesUrl}
                    className={cn(
                        'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90',
                    )}
                    style={{ backgroundColor: accent }}
                >
                    <Icon name="bs.collection" className="text-base" />
                    {t('View Instances')}
                </Link>
            </CardFooter>
        </Card>
    );
}

function MetaItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-[var(--color-orbit-panel-border,#e2e8f0)] bg-[var(--color-orbit-nav-section-bg,#f8fafc)] px-3 py-2.5">
            <dt className="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-orbit-secondary,#94a3b8)]">
                {label}
            </dt>
            <dd className="mt-1 text-sm font-medium text-[var(--color-orbit-secondary,#0f172a)]">{value}</dd>
        </div>
    );
}

function PaginationLink({
    href,
    icon,
    trailingIcon,
    children,
}: {
    href?: string | null;
    icon?: string;
    trailingIcon?: string;
    children: string;
}) {
    if (!href) {
        return (
            <span className="inline-flex items-center gap-2 rounded-lg border border-[var(--color-orbit-panel-border,#e2e8f0)] px-3 py-2 text-sm text-[var(--color-orbit-secondary,#94a3b8)] opacity-60">
                {icon ? <Icon name={icon} className="text-base" /> : null}
                {children}
                {trailingIcon ? <Icon name={trailingIcon} className="text-base" /> : null}
            </span>
        );
    }

    return (
        <Link
            href={href}
            className="inline-flex items-center gap-2 rounded-lg border border-[var(--color-orbit-panel-border,#e2e8f0)] px-3 py-2 text-sm font-medium text-[var(--color-orbit-secondary,#334155)] transition hover:bg-[var(--color-orbit-nav-section-bg,#f8fafc)]"
        >
            {icon ? <Icon name={icon} className="text-base" /> : null}
            {children}
            {trailingIcon ? <Icon name={trailingIcon} className="text-base" /> : null}
        </Link>
    );
}
