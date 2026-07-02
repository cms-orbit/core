import { useMemo, useState } from 'react';
import type { LayoutComponentProps } from '../contract';
import { useT } from '../lib/i18n';
import { LayoutNodeRenderer } from '../screen-renderer';
import { Card, CardBody } from '../ui/card';
import { Icon } from '../ui/icon';

interface LocaleOption {
    code: string;
    label: string;
}

/**
 * Renders one tab per content locale for translatable fields. The server emits
 * `type: "locale-tabs"` with `locales` metadata and one `tab-pane` child per
 * locale (fields inside are already name-scoped to the locale, e.g. ko[title]).
 */
export function LocaleTabsLayout({ node, data, screen }: LayoutComponentProps) {
    const t = useT();
    const panes = node.children;
    const locales = useMemo(
        () => (node.data.locales as LocaleOption[] | undefined) ?? [],
        [node.data.locales],
    );
    const activeTab = node.data.activeTab as string | undefined;

    const initial = Math.max(
        0,
        panes.findIndex((pane) => pane.key === activeTab),
    );
    const [active, setActive] = useState(initial);

    const labelFor = (index: number): string =>
        locales[index]?.label ?? (panes[index]?.data.title as string | undefined) ?? `#${index + 1}`;

    const codeFor = (index: number): string | null =>
        locales[index]?.code ?? (panes[index]?.data.locale as string | undefined) ?? null;

    return (
        <Card>
            <div className="flex items-center gap-1 border-b border-gray-100 px-3 dark:border-gray-700">
                <span className="flex items-center pr-2 text-gray-400" title={t('Language')}>
                    <Icon name="bs.translate" className="text-base" />
                </span>
                {panes.map((pane, index) => (
                    <button
                        key={pane.key}
                        type="button"
                        onClick={() => setActive(index)}
                        className={
                            index === active
                                ? 'flex items-center gap-1.5 border-b-2 border-orbit-primary px-3 py-2 text-sm font-medium text-orbit-primary'
                                : 'flex items-center gap-1.5 px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        }
                    >
                        {labelFor(index)}
                        {codeFor(index) ? (
                            <span className="rounded bg-gray-100 px-1.5 text-[10px] font-semibold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                {codeFor(index)}
                            </span>
                        ) : null}
                    </button>
                ))}
            </div>
            <CardBody>
                {panes[active] ? (
                    <LayoutNodeRenderer node={panes[active]} data={data} screen={screen} />
                ) : null}
            </CardBody>
        </Card>
    );
}
