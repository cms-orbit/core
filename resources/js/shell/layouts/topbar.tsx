import { Link } from '@inertiajs/react';
import type { LayoutViewProps, MenuSection, OrbitMenuItem } from '../parts';
import {
    Breadcrumbs,
    HeaderActions,
    MobileMenuButton,
    PageBody,
    SidebarPanel,
    itemTreeActive,
    pathMatches,
    toPath,
    useMenuState,
} from '../parts';
import { cn } from '../../lib/cn';
import { useT } from '../../lib/i18n';
import { Icon } from '../../ui/icon';

/** Flatten a section's items (and their children) into a single link list. */
function flattenItems(section: MenuSection | null): OrbitMenuItem[] {
    if (!section) {
        return [];
    }

    return section.items.flatMap((item) =>
        (item.children?.length ?? 0) > 0 ? item.children! : [item],
    );
}

/**
 * Top bar: a horizontal section menu tree. The primary sections sit in the top
 * bar; the selected section's items appear in a secondary horizontal sub-nav.
 */
export function TopbarLayoutView({ orbit, brand, currentPath, breadcrumbs, ...content }: LayoutViewProps) {
    const t = useT();
    const { sections, activeKey, selectedKey, setSelectedKey, mobileOpen, setMobileOpen, selectedSection } =
        useMenuState(orbit.menu ?? [], currentPath, orbit.sections ?? {});

    const items = flattenItems(selectedSection);

    return (
        <div className="flex min-h-screen flex-col bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
            <header className="flex h-14 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 md:px-6 dark:border-gray-800 dark:bg-gray-900">
                <div className="flex min-w-0 items-center gap-4">
                    <MobileMenuButton onClick={() => setMobileOpen((open) => !open)} />
                    <Link href="/" className="flex items-center gap-2" title={brand?.name ?? 'Orbit'}>
                        {brand?.logo ? (
                            <img src={brand.logo} alt={brand?.name ?? 'Orbit'} className="h-5 w-auto" />
                        ) : (
                            <span className="text-lg font-semibold">{brand?.name ?? 'Orbit'}</span>
                        )}
                    </Link>
                    <nav className="hidden items-center gap-1 md:flex">
                        {sections.map((section) => {
                            const isSelected = section.key === selectedKey;
                            const isActive = section.key === activeKey;

                            return (
                                <button
                                    key={section.key}
                                    type="button"
                                    onClick={() => setSelectedKey(section.key)}
                                    className={cn(
                                        'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm',
                                        isSelected || isActive
                                            ? 'bg-orbit-primary/10 font-medium text-orbit-primary'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                                    )}
                                >
                                    <Icon name={section.icon} className="text-base" />
                                    {t(section.label)}
                                </button>
                            );
                        })}
                    </nav>
                </div>
                <HeaderActions user={orbit.user ?? null} />
            </header>

            {items.length > 0 ? (
                <div className="hidden items-center gap-1 overflow-x-auto border-b border-gray-200 bg-white px-4 md:flex md:px-6 dark:border-gray-800 dark:bg-gray-900">
                    {items.map((item, index) => {
                        const active = itemTreeActive(item, currentPath) || pathMatches(toPath(item.url), currentPath);

                        return item.url ? (
                            <Link
                                key={`${item.label}-${index}`}
                                href={item.url}
                                className={cn(
                                    'flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2 text-sm',
                                    active
                                        ? 'border-orbit-primary font-medium text-orbit-primary'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                                )}
                            >
                                {item.icon ? <Icon name={item.icon} className="text-base" /> : null}
                                {t(item.label)}
                            </Link>
                        ) : null;
                    })}
                </div>
            ) : null}

            <div className="flex-1">
                {breadcrumbs && breadcrumbs.length > 0 ? (
                    <div className="mx-auto max-w-6xl px-4 pt-4 md:px-6">
                        <Breadcrumbs items={breadcrumbs} />
                    </div>
                ) : null}
                <PageBody {...content} />
            </div>

            {mobileOpen ? (
                <div className="fixed inset-0 z-40 flex md:hidden">
                    <div className="absolute inset-0 bg-black/40" onClick={() => setMobileOpen(false)} aria-hidden />
                    <div className="relative z-10 flex h-full">
                        <SidebarPanel
                            brand={brand}
                            sections={sections}
                            showAllSections
                            currentPath={currentPath}
                            user={orbit.user ?? null}
                        />
                    </div>
                </div>
            ) : null}
        </div>
    );
}
