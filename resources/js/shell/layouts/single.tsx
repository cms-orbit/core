import type { LayoutViewProps } from '../parts';
import {
    Breadcrumbs,
    HeaderActions,
    MobileMenuButton,
    PageBody,
    SidebarPanel,
    useMenuState,
} from '../parts';

/**
 * Single classic sidebar: one column lists every section (header + items),
 * no icon rail.
 */
export function SingleLayoutView({ orbit, brand, currentPath, breadcrumbs, ...content }: LayoutViewProps) {
    const { sections, mobileOpen, setMobileOpen } = useMenuState(
        orbit.menu ?? [],
        currentPath,
        orbit.sections ?? {},
    );
    const homeUrl = orbit.home ?? '/main';
    const contentWidth = content.contentWidth ?? brand?.contentWidth;

    return (
        <div
            className="flex min-h-screen"
            style={{
                backgroundColor: 'var(--color-orbit-page-bg, #f8fafc)',
                color: 'var(--color-orbit-secondary, #0f172a)',
            }}
        >
            <div className="hidden md:block">
                <SidebarPanel
                    brand={brand}
                    homeUrl={homeUrl}
                    sections={sections}
                    showAllSections
                    currentPath={currentPath}
                    user={orbit.user ?? null}
                />
            </div>

            <div className="flex min-w-0 flex-1 flex-col">
                <header
                    className="flex h-14 items-center justify-between gap-3 border-b px-4 md:px-6"
                    style={{
                        backgroundColor: 'var(--color-orbit-header-bg, #ffffff)',
                        borderColor: 'var(--color-orbit-header-border, #e2e8f0)',
                    }}
                >
                    <div className="flex min-w-0 items-center gap-3">
                        <MobileMenuButton onClick={() => setMobileOpen((open) => !open)} />
                        <Breadcrumbs items={breadcrumbs} />
                    </div>
                    <HeaderActions
                        user={orbit.user ?? null}
                        themeMode={brand?.themeMode ?? brand?.darkMode}
                        themeToggleEnabled={brand?.themeToggleEnabled}
                        showUserMenu={false}
                    />
                </header>

                <PageBody {...content} contentWidth={contentWidth} />
            </div>

            {mobileOpen ? (
                <div className="fixed inset-0 z-40 flex md:hidden">
                    <div
                        className="absolute inset-0 bg-black/40"
                        onClick={() => setMobileOpen(false)}
                        aria-hidden
                    />
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
