import type { LayoutViewProps } from '../parts';
import {
    Breadcrumbs,
    HeaderActions,
    IconRail,
    MobileMenuButton,
    PageBody,
    SidebarPanel,
    useMenuState,
} from '../parts';

/**
 * Palette split sidebar: compact rail + secondary sidebar, both themed with
 * dedicated surface and active-state tokens.
 */
export function PaletteSplitLayoutView({
    orbit,
    brand,
    currentPath,
    breadcrumbs,
    ...content
}: LayoutViewProps) {
    const { sections, activeKey, setSelectedKey, mobileOpen, setMobileOpen, selectedSection } = useMenuState(
        orbit.menu ?? [],
        currentPath,
        orbit.sections ?? {},
    );
    const homeUrl = orbit.home ?? '/main';

    return (
        <div
            className="flex min-h-screen"
            style={{
                backgroundColor: 'var(--color-orbit-page-bg, #f8fafc)',
                color: 'var(--color-orbit-secondary, #0f172a)',
            }}
        >
            <IconRail
                brand={brand}
                homeUrl={homeUrl}
                sections={sections}
                selectedKey={selectedSection?.key ?? null}
                activeKey={activeKey}
                onSelect={setSelectedKey}
            />

            <div className="hidden md:block">
                <SidebarPanel
                    brand={brand}
                    homeUrl={homeUrl}
                    section={selectedSection}
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
                    <HeaderActions user={orbit.user ?? null} darkMode={brand?.darkMode} />
                </header>

                <PageBody {...content} />
            </div>

            {mobileOpen ? (
                <div className="fixed inset-0 z-40 flex md:hidden">
                    <div
                        className="absolute inset-0 bg-black/40"
                        onClick={() => setMobileOpen(false)}
                        aria-hidden
                    />
                    <div className="relative z-10 flex h-full">
                        <IconRail
                            brand={brand}
                            homeUrl={homeUrl}
                            sections={sections}
                            selectedKey={selectedSection?.key ?? null}
                            activeKey={activeKey}
                            onSelect={setSelectedKey}
                            variant="mobile"
                        />
                        <SidebarPanel
                            brand={brand}
                            homeUrl={homeUrl}
                            section={selectedSection}
                            currentPath={currentPath}
                            user={orbit.user ?? null}
                        />
                    </div>
                </div>
            ) : null}
        </div>
    );
}
