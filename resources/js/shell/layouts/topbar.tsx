import { Link } from '@inertiajs/react';
import { cn } from '../../lib/cn';
import { useT } from '../../lib/i18n';
import { Icon } from '../../ui/icon';
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
    const homeUrl = orbit.home ?? '/main';

    const items = flattenItems(selectedSection);

    return (
        <div
            className="flex min-h-screen flex-col"
            style={{
                backgroundColor: 'var(--color-orbit-page-bg, #f8fafc)',
                color: 'var(--color-orbit-secondary, #0f172a)',
            }}
        >
            <header
                className="flex h-14 items-center justify-between gap-3 border-b px-4 md:px-6"
                style={{
                    backgroundColor: 'var(--color-orbit-header-bg, #ffffff)',
                    borderColor: 'var(--color-orbit-header-border, #e2e8f0)',
                }}
            >
                <div className="flex min-w-0 items-center gap-4">
                    <MobileMenuButton onClick={() => setMobileOpen((open) => !open)} />
                    <Link href={homeUrl} className="flex items-center gap-2" title={brand?.name ?? 'Orbit'}>
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
                                        isSelected || isActive ? 'font-medium' : '',
                                    )}
                                    style={{
                                        backgroundColor:
                                            isSelected || isActive
                                                ? 'var(--color-orbit-nav-active-bg, rgba(23, 206, 145, 0.12))'
                                                : 'transparent',
                                        color:
                                            isSelected || isActive
                                                ? 'var(--color-orbit-nav-active-fg, #0f766e)'
                                                : 'var(--color-orbit-secondary, #475569)',
                                    }}
                                >
                                    <Icon name={section.icon} className="text-base" />
                                    {t(section.label)}
                                </button>
                            );
                        })}
                    </nav>
                </div>
                <HeaderActions user={orbit.user ?? null} darkMode={brand?.darkMode} />
            </header>

            {items.length > 0 ? (
                <div
                    className="hidden items-center gap-1 overflow-x-auto border-b px-4 md:flex md:px-6"
                    style={{
                        backgroundColor: 'var(--color-orbit-nav-bg, #f8fafc)',
                        borderColor: 'var(--color-orbit-nav-border, #e2e8f0)',
                    }}
                >
                    {items.map((item, index) => {
                        const active = itemTreeActive(item, currentPath) || pathMatches(toPath(item.url), currentPath);

                        return item.url ? (
                            <Link
                                key={`${item.label}-${index}`}
                                href={item.url}
                                className={cn(
                                    'flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2 text-sm',
                                    active ? 'font-medium' : 'border-transparent',
                                )}
                                style={{
                                    borderColor: active
                                        ? 'var(--color-orbit-primary, #17ce91)'
                                        : 'transparent',
                                    color: active
                                        ? 'var(--color-orbit-nav-active-fg, #0f766e)'
                                        : 'var(--color-orbit-secondary, #64748b)',
                                }}
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
                            homeUrl={homeUrl}
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
