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
 * Split sidebar (default): a compact icon rail selects a section, a secondary
 * sidebar lists that section's items.
 */
export function SplitLayoutView({ orbit, brand, currentPath, breadcrumbs, ...content }: LayoutViewProps) {
    const { sections, activeKey, selectedKey, setSelectedKey, mobileOpen, setMobileOpen, selectedSection } =
        useMenuState(orbit.menu ?? [], currentPath, orbit.sections ?? {});

    return (
        <div className="flex min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
            <IconRail
                brand={brand}
                sections={sections}
                selectedKey={selectedSection?.key ?? null}
                activeKey={activeKey}
                onSelect={setSelectedKey}
            />

            <div className="hidden md:block">
                <SidebarPanel
                    brand={brand}
                    section={selectedSection}
                    currentPath={currentPath}
                    user={orbit.user ?? null}
                />
            </div>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex h-14 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 md:px-6 dark:border-gray-800 dark:bg-gray-900">
                    <div className="flex min-w-0 items-center gap-3">
                        <MobileMenuButton onClick={() => setMobileOpen((open) => !open)} />
                        <Breadcrumbs items={breadcrumbs} />
                    </div>
                    <HeaderActions user={orbit.user ?? null} />
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
                            sections={sections}
                            selectedKey={selectedSection?.key ?? null}
                            activeKey={activeKey}
                            onSelect={setSelectedKey}
                            variant="mobile"
                        />
                        <SidebarPanel
                            brand={brand}
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
