import { Link } from '@inertiajs/react';
import type { LayoutViewProps } from '../parts';
import {
    Breadcrumbs,
    HeaderActions,
    MobileMenuButton,
    PageBody,
    SectionNav,
    SidebarPanel,
    UserMenu,
    useMenuState,
} from '../parts';
import { cn } from '../../lib/cn';
import { useT } from '../../lib/i18n';
import { Icon } from '../../ui/icon';

/**
 * Hybrid: primary sections live in the top bar, the selected section's items
 * live in a left sidebar beneath it.
 */
export function HybridLayoutView({ orbit, brand, currentPath, breadcrumbs, ...content }: LayoutViewProps) {
    const t = useT();
    const { sections, activeKey, selectedKey, setSelectedKey, mobileOpen, setMobileOpen, selectedSection } =
        useMenuState(orbit.menu ?? [], currentPath, orbit.sections ?? {});

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

            <div className="flex min-h-0 flex-1">
                <aside className="hidden w-56 shrink-0 flex-col border-r border-gray-200 bg-white md:flex dark:border-gray-800 dark:bg-gray-900">
                    <div className="flex-1 overflow-y-auto px-2 py-3">
                        <SectionNav section={selectedSection} currentPath={currentPath} />
                    </div>
                    <div className="border-t border-gray-200 p-3 dark:border-gray-800">
                        <UserMenu user={orbit.user ?? null} compact />
                    </div>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    {breadcrumbs && breadcrumbs.length > 0 ? (
                        <div className="mx-auto w-full max-w-6xl px-4 pt-4 md:px-6">
                            <Breadcrumbs items={breadcrumbs} />
                        </div>
                    ) : null}
                    <PageBody {...content} />
                </div>
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
