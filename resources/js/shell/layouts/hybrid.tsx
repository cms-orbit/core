import { Link } from '@inertiajs/react';
import { cn } from '../../lib/cn';
import { useT } from '../../lib/i18n';
import { resolveBrandAsset } from '../../theme/branding';
import { Icon } from '../../ui/icon';
import type { LayoutViewProps } from '../parts';
import {
    Breadcrumbs,
    ContentContainer,
    HeaderActions,
    MobileMenuButton,
    PageBody,
    SectionNav,
    SidebarPanel,
    getSectionDirectUrl,
    hasSectionSidebarContent,
    UserMenu,
    splitSectionsByPlacement,
    useMenuState,
} from '../parts';

/**
 * Hybrid: primary sections live in the top bar, the selected section's items
 * live in a left sidebar beneath it.
 */
export function HybridLayoutView({ orbit, brand, currentPath, breadcrumbs, ...content }: LayoutViewProps) {
    const t = useT();
    const logoUrl = resolveBrandAsset(brand, 'logo');
    const { sections, activeKey, selectedKey, setSelectedKey, mobileOpen, setMobileOpen, selectedSection } =
        useMenuState(orbit.menu ?? [], currentPath, orbit.sections ?? {});
    const homeUrl = orbit.home ?? '/main';
    const { primary: primarySections, secondary: secondarySections } = splitSectionsByPlacement(sections, 'topbar');
    const contentWidth = content.contentWidth ?? brand?.contentWidth;
    const showSidebarPanel = hasSectionSidebarContent(selectedSection);

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
                        {logoUrl ? (
                            <img src={logoUrl} alt={brand?.name ?? 'Orbit'} className="h-5 w-auto object-contain" />
                        ) : (
                            <span className="text-lg font-semibold">{brand?.name ?? 'Orbit'}</span>
                        )}
                    </Link>
                    <nav className="hidden items-center gap-1 md:flex">
                        {primarySections.map((section) => {
                            const url = getSectionDirectUrl(section);
                            const isSelected = section.key === selectedKey;
                            const isActive = section.key === activeKey;
                            const className = cn(
                                'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm',
                                isSelected || isActive ? 'font-medium' : '',
                            );
                            const style = {
                                backgroundColor:
                                    isSelected || isActive
                                        ? 'var(--color-orbit-nav-active-bg, #d1fae5)'
                                        : 'transparent',
                                color:
                                    isSelected || isActive
                                        ? 'var(--color-orbit-nav-active-fg, #047857)'
                                        : 'var(--color-orbit-secondary, #475569)',
                            };
                            const content = (
                                <>
                                    <Icon name={section.icon} className="text-base" />
                                    {t(section.label)}
                                </>
                            );

                            if (url) {
                                return (
                                    <Link key={section.key} href={url} className={className} style={style}>
                                        {content}
                                    </Link>
                                );
                            }

                            return (
                                <button
                                    key={section.key}
                                    type="button"
                                    onClick={() => setSelectedKey(section.key)}
                                    className={className}
                                    style={style}
                                >
                                    {content}
                                </button>
                            );
                        })}
                    </nav>
                </div>
                <div className="flex items-center gap-3">
                    {secondarySections.length > 0 ? (
                        <nav className="hidden items-center gap-1 md:flex">
                            {secondarySections.map((section) => {
                                const url = getSectionDirectUrl(section);
                                const isSelected = section.key === selectedKey;
                                const isActive = section.key === activeKey;
                                const className = cn(
                                    'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm',
                                    isSelected || isActive ? 'font-medium' : '',
                                );
                                const style = {
                                    backgroundColor:
                                        isSelected || isActive
                                            ? 'var(--color-orbit-nav-active-bg, #d1fae5)'
                                            : 'transparent',
                                    color:
                                        isSelected || isActive
                                            ? 'var(--color-orbit-nav-active-fg, #047857)'
                                            : 'var(--color-orbit-secondary, #475569)',
                                };
                                const content = (
                                    <>
                                        <Icon name={section.icon} className="text-base" />
                                        {t(section.label)}
                                    </>
                                );

                                if (url) {
                                    return (
                                        <Link key={section.key} href={url} className={className} style={style}>
                                            {content}
                                        </Link>
                                    );
                                }

                                return (
                                    <button
                                        key={section.key}
                                        type="button"
                                        onClick={() => setSelectedKey(section.key)}
                                        className={className}
                                        style={style}
                                    >
                                        {content}
                                    </button>
                                );
                            })}
                        </nav>
                    ) : null}
                    <HeaderActions
                        user={orbit.user ?? null}
                        themeMode={brand?.themeMode ?? brand?.darkMode}
                        themeToggleEnabled={brand?.themeToggleEnabled}
                        showUserMenu={false}
                    />
                </div>
            </header>

            <div className="flex min-h-0 flex-1">
                {showSidebarPanel ? (
                    <aside
                        className="sticky top-0 hidden h-screen w-56 shrink-0 self-start flex-col border-r md:flex"
                        style={{
                            backgroundColor: 'var(--color-orbit-nav-bg, #ffffff)',
                            borderColor: 'var(--color-orbit-nav-border, #e2e8f0)',
                        }}
                    >
                        <div className="min-h-0 flex-1 overflow-y-auto px-2 py-3">
                            <SectionNav section={selectedSection} currentPath={currentPath} headerStyle="hidden" />
                        </div>
                        <div
                            className="border-t p-3"
                            style={{ borderColor: 'var(--color-orbit-nav-border, #e2e8f0)' }}
                        >
                            <UserMenu user={orbit.user ?? null} compact />
                        </div>
                    </aside>
                ) : null}

                <div className="flex min-w-0 flex-1 flex-col">
                    {breadcrumbs && breadcrumbs.length > 0 ? (
                        <ContentContainer contentWidth={contentWidth} className="px-4 pt-4 md:px-6">
                            <Breadcrumbs items={breadcrumbs} />
                        </ContentContainer>
                    ) : null}
                    <PageBody {...content} contentWidth={contentWidth} />
                </div>
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
                            sectionHeaderStyle="hidden"
                            currentPath={currentPath}
                            user={orbit.user ?? null}
                        />
                    </div>
                </div>
            ) : null}
        </div>
    );
}
