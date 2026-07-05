import type { CustomComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import type { ReactNode } from 'react';

type DetailRow = {
    label: string;
    value?: string;
    html?: string;
};

type VisitorSummary = {
    total_visits: number;
    other_visits: number;
    first_seen: string | null;
    last_seen: string | null;
};

function DetailSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <h3 className="border-b border-gray-100 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                {title}
            </h3>
            {children}
        </section>
    );
}

function DetailRows({ rows }: { rows: DetailRow[] }) {
    return (
        <dl className="space-y-2">
            {rows.map((row) => (
                <div key={row.label} className="grid grid-cols-[minmax(0,8rem)_1fr] gap-3 text-sm">
                    <dt className="text-gray-500 dark:text-gray-400">{row.label}</dt>
                    <dd className="min-w-0 text-gray-900 dark:text-gray-100">
                        {row.html ? (
                            <span dangerouslySetInnerHTML={{ __html: row.html }} />
                        ) : (
                            row.value || '—'
                        )}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function deviceTone(device: string): string {
    switch (device) {
        case 'mobile':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
        case 'tablet':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
        case 'bot':
            return 'bg-red-500/10 text-red-700 dark:text-red-300';
        default:
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-300';
    }
}

/** Read-only visitor record detail panel with visit, visitor, and technical metadata. */
export function VisitorRecordDetailView({ props: customProps }: CustomComponentProps) {
    const t = useT();
    const pagePath = String(customProps?.page_path ?? '/');
    const routeName = customProps?.route_name as string | null | undefined;
    const routeUri = customProps?.route_uri as string | null | undefined;
    const browserFamily = String(customProps?.browser_family ?? t('Unknown'));
    const deviceType = String(customProps?.device_type ?? 'unknown');
    const userAgent = String(customProps?.user_agent ?? '');
    const visitorHash = String(customProps?.visitor_hash ?? '');
    const summary = (customProps?.summary as VisitorSummary | undefined) ?? {
        total_visits: 0,
        other_visits: 0,
        first_seen: null,
        last_seen: null,
    };

    const visitRows = (customProps?.visit_rows as DetailRow[] | undefined) ?? [];
    const visitorRows = (customProps?.visitor_rows as DetailRow[] | undefined) ?? [];
    const networkRows = (customProps?.network_rows as DetailRow[] | undefined) ?? [];

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-gray-200 bg-gradient-to-br from-orbit-primary/5 via-white to-white p-5 dark:border-gray-700 dark:from-orbit-primary/10 dark:via-gray-900 dark:to-gray-900">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0 flex-1">
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {t('Current page')}
                        </p>
                        <p className="mt-1 break-all font-mono text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {pagePath}
                        </p>
                        {routeName ? (
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {routeName}
                                {routeUri ? ` · ${routeUri}` : ''}
                            </p>
                        ) : null}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <span
                            className={cn(
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                deviceTone(deviceType),
                            )}
                        >
                            {deviceType}
                        </span>
                        <span className="inline-flex items-center rounded-full bg-slate-500/10 px-2.5 py-0.5 text-xs font-medium text-slate-700 dark:text-slate-300">
                            {browserFamily}
                        </span>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <SummaryStat label={t('Total visits')} value={String(summary.total_visits)} />
                <SummaryStat label={t('Other visits')} value={String(summary.other_visits)} />
                <SummaryStat label={t('First seen')} value={summary.first_seen ?? '—'} />
                <SummaryStat label={t('Last seen')} value={summary.last_seen ?? '—'} />
            </div>

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <DetailSection title={t('Visit')}>
                    <DetailRows rows={visitRows} />
                </DetailSection>

                <DetailSection title={t('Visitor')}>
                    <DetailRows rows={visitorRows} />
                </DetailSection>
            </div>

            <DetailSection title={t('Network')}>
                <DetailRows rows={networkRows} />
            </DetailSection>

            <DetailSection title={t('User Agent')}>
                {userAgent ? (
                    <pre className="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-slate-950/95 p-3 text-xs leading-relaxed break-all whitespace-pre-wrap text-slate-100 dark:border-gray-700">
                        {userAgent}
                    </pre>
                ) : (
                    <p className="text-sm text-gray-400">{t('Not recorded')}</p>
                )}
                {visitorHash ? (
                    <p className="mt-2 font-mono text-[11px] text-gray-500 dark:text-gray-400">
                        {t('Visitor hash')}: {visitorHash}
                    </p>
                ) : null}
            </DetailSection>
        </div>
    );
}

function SummaryStat({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-700 dark:bg-gray-900/40">
            <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {label}
            </p>
            <p className="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{value}</p>
        </div>
    );
}
