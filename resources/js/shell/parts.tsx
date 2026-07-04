import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '../contract';
import { cn } from '../lib/cn';
import type { OrbitI18n } from '../lib/i18n';
import { useT } from '../lib/i18n';
import type { DarkModeSetting, OrbitBrand } from '../theme/branding';
import { Icon } from '../ui/icon';
import { LanguageSwitcher } from '../ui/language-switcher';
import { NotificationCenter } from '../ui/notification';
import { ThemeModeSwitcher } from '../ui/theme-mode-switcher';

export interface OrbitMenuItem {
    label: string;
    url?: string | null;
    icon?: string | null;
    badge?: string | number | null;
    section?: string | null;
    sectionKey?: string | null;
    sort?: number;
    divider?: boolean;
    active?: boolean;
    children?: OrbitMenuItem[];
}

export interface OrbitMenuSectionRegistry {
    icon: string;
    label?: string | null;
    sort?: number;
}

export interface SharedOrbit {
    menu?: OrbitMenuItem[];
    sections?: Record<string, OrbitMenuSectionRegistry>;
    permissions?: string[];
    home?: string | null;
    user?: { id: number | string; name?: string; email?: string } | null;
    flash?: { message?: string | null; type?: string | null };
    brand?: OrbitBrand;
    i18n?: OrbitI18n;
}

export interface MenuSection {
    key: string;
    label: string;
    icon: string;
    sort: number;
    items: OrbitMenuItem[];
}

/** Content props shared by every layout mode. */
export interface DashboardContentProps {
    title?: string | null;
    description?: string | null;
    breadcrumbs?: Breadcrumb[];
    actions?: ReactNode;
    children: ReactNode;
}

/** Props a concrete layout view receives from the dispatcher. */
export interface LayoutViewProps extends DashboardContentProps {
    orbit: SharedOrbit;
    brand?: OrbitBrand;
    currentPath: string;
}

export const UNSECTIONED = '__general__';

function toneVar(name: string, fallback: string): string {
    return `var(--color-orbit-${name}, ${fallback})`;
}

/** Section name → rail/nav glyph. Falls back to the first item's icon. */
export const SECTION_ICONS: Record<string, string> = {
    'Access Control': 'bs.shield-lock',
    System: 'bs.gear',
    Settings: 'bs.gear',
    Entities: 'bs.grid',
    Content: 'bs.layers',
    Documents: 'bs.file-earmark-text',
    문서: 'bs.file-earmark-text',
    Media: 'bs.images',
};

/** Strip scheme + host so absolute route URLs compare cleanly to the SPA path. */
export function toPath(url?: string | null): string | null {
    if (!url) {
        return null;
    }

    return url.replace(/^https?:\/\/[^/]+/i, '') || '/';
}

export function pathMatches(itemPath: string | null, currentPath: string): boolean {
    if (!itemPath) {
        return false;
    }

    if (itemPath === '/') {
        return currentPath === '/';
    }

    return currentPath === itemPath || currentPath.startsWith(`${itemPath}/`);
}

export function itemTreeActive(item: OrbitMenuItem, currentPath: string): boolean {
    if (pathMatches(toPath(item.url), currentPath)) {
        return true;
    }

    return (item.children ?? []).some((child) => itemTreeActive(child, currentPath));
}

/** Group the flat, pre-sorted menu into sections for rails / sidebars / nav. */
export function buildSections(
    menu: OrbitMenuItem[],
    registry: Record<string, OrbitMenuSectionRegistry> = {},
): MenuSection[] {
    const map = new Map<string, MenuSection>();

    menu.forEach((item, index) => {
        const sectionKey = item.sectionKey?.trim() || null;
        const label = item.section?.trim() || '';
        const key = sectionKey ?? (label || UNSECTIONED);
        const registered = sectionKey ? registry[sectionKey] : undefined;

        if (!map.has(key)) {
            map.set(key, {
                key,
                label: registered?.label ?? (label || 'General'),
                icon:
                    registered?.icon ??
                    SECTION_ICONS[label] ??
                    item.icon ??
                    'bs.grid',
                sort: registered?.sort ?? item.sort ?? index,
                items: [],
            });
        }

        const section = map.get(key)!;
        section.sort = Math.min(section.sort, registered?.sort ?? item.sort ?? index);
        section.items.push(item);
    });

    return Array.from(map.values()).sort((a, b) => a.sort - b.sort);
}

/** Shared menu/section selection + mobile drawer state used by every layout. */
export function useMenuState(
    menu: OrbitMenuItem[],
    currentPath: string,
    registry: Record<string, OrbitMenuSectionRegistry> = {},
) {
    const sections = useMemo(() => buildSections(menu, registry), [menu, registry]);

    const activeKey = useMemo(() => {
        const match = sections.find((section) =>
            section.items.some((item) => itemTreeActive(item, currentPath)),
        );

        return match?.key ?? sections[0]?.key ?? null;
    }, [sections, currentPath]);

    const [selectionState, setSelectionState] = useState<{ path: string; key: string | null } | null>(null);
    const [mobileOpenState, setMobileOpenState] = useState<{ path: string; open: boolean }>({
        path: currentPath,
        open: false,
    });

    const selectedKey =
        selectionState?.path === currentPath
            ? selectionState.key
            : activeKey ?? sections[0]?.key ?? null;
    const mobileOpen = mobileOpenState.path === currentPath ? mobileOpenState.open : false;

    const setSelectedKey = (key: string | null) => {
        setSelectionState({ path: currentPath, key });
    };

    const setMobileOpen = (value: boolean | ((open: boolean) => boolean)) => {
        const next = typeof value === 'function' ? value(mobileOpen) : value;

        setMobileOpenState({ path: currentPath, open: next });
    };

    const selectedSection =
        sections.find((section) => section.key === selectedKey) ?? sections[0] ?? null;

    return {
        sections,
        activeKey,
        selectedKey,
        setSelectedKey,
        mobileOpen,
        setMobileOpen,
        selectedSection,
    };
}

/** Discord-style primary icon rail: brand mark + section icons. */
export function IconRail({
    brand,
    homeUrl = '/main',
    sections,
    selectedKey,
    activeKey,
    onSelect,
    variant = 'desktop',
}: {
    brand?: OrbitBrand;
    homeUrl?: string;
    sections: MenuSection[];
    selectedKey: string | null;
    activeKey: string | null;
    onSelect: (key: string) => void;
    variant?: 'desktop' | 'mobile';
}) {
    const t = useT();

    return (
        <aside
            className={cn(
                'w-16 shrink-0 flex-col items-center gap-1 py-3',
                variant === 'mobile' ? 'flex' : 'hidden md:flex',
            )}
            style={{
                backgroundColor: toneVar('rail-bg', '#ffffff'),
                borderRight: `1px solid ${toneVar('rail-border', '#e2e8f0')}`,
            }}
        >
            <Link
                href={homeUrl}
                className="mb-2 flex h-11 w-11 items-center justify-center rounded-2xl transition hover:rounded-xl"
                title={brand?.name ?? 'Orbit'}
                style={{ backgroundColor: toneVar('rail-active-bg', 'rgba(23, 206, 145, 0.12)') }}
            >
                {brand?.symbol ? (
                    <img src={brand.symbol} alt={brand?.name ?? 'Orbit'} className="h-6 w-6" />
                ) : (
                    <span className="text-sm font-bold" style={{ color: toneVar('primary', '#17ce91') }}>
                        {(brand?.name ?? 'O').charAt(0)}
                    </span>
                )}
            </Link>

            <div className="mb-1 h-px w-8" style={{ backgroundColor: toneVar('rail-border', '#e2e8f0') }} />

            <nav className="flex flex-1 flex-col items-center gap-1">
                {sections.map((section) => {
                    const isSelected = section.key === selectedKey;
                    const isActive = section.key === activeKey;

                    return (
                        <button
                            key={section.key}
                            type="button"
                            onClick={() => onSelect(section.key)}
                            title={t(section.label)}
                            className={cn(
                                'group relative flex h-11 w-11 items-center justify-center rounded-2xl transition hover:rounded-xl',
                            )}
                            style={{
                                backgroundColor: isSelected ? toneVar('rail-active-bg', '#17ce91') : 'transparent',
                                color: isSelected
                                    ? toneVar('rail-active-fg', '#ffffff')
                                    : toneVar('rail-icon', '#64748b'),
                            }}
                        >
                            <span
                                className={cn(
                                    'absolute -left-3 w-1 rounded-r-full transition-all',
                                    isActive || isSelected ? 'opacity-100' : 'opacity-0',
                                    isSelected ? 'h-8' : 'h-5',
                                )}
                                style={{ backgroundColor: toneVar('primary', '#17ce91') }}
                            />
                            <Icon name={section.icon} className="text-xl" />
                        </button>
                    );
                })}
            </nav>
        </aside>
    );
}

/** Brand wordmark row used at the top of sidebars. */
export function BrandWordmark({ brand, homeUrl = '/main' }: { brand?: OrbitBrand; homeUrl?: string }) {
    return (
        <div
            className="flex h-14 items-center gap-2 border-b px-4"
            style={{ borderColor: toneVar('nav-border', '#e2e8f0') }}
        >
            <Link href={homeUrl} className="flex items-center gap-2" title={brand?.name ?? 'Orbit'}>
                {brand?.logo ? (
                    <img src={brand.logo} alt={brand?.name ?? 'Orbit'} className="h-5 w-auto" />
                ) : (
                    <span className="text-lg font-semibold" style={{ color: toneVar('secondary', '#0f172a') }}>
                        {brand?.name ?? 'Orbit'}
                    </span>
                )}
            </Link>
        </div>
    );
}

/** A single section's grouped links (header + items). */
export function SectionNav({
    section,
    currentPath,
    showHeader = true,
}: {
    section: MenuSection | null;
    currentPath: string;
    showHeader?: boolean;
}) {
    const t = useT();

    if (!section) {
        return (
            <p className="px-3 py-2 text-sm" style={{ color: toneVar('nav-muted', '#94a3b8') }}>
                {t('No menu items.')}
            </p>
        );
    }

    return (
        <>
            {showHeader ? (
                <p
                    className="px-3 pb-1 text-xs font-semibold uppercase tracking-wide"
                    style={{ color: toneVar('nav-muted', '#94a3b8') }}
                >
                    {t(section.label)}
                </p>
            ) : null}
            <nav className="space-y-0.5">
                {section.items.map((item, index) => (
                    <SidebarItem key={`${item.label}-${index}`} item={item} currentPath={currentPath} />
                ))}
            </nav>
        </>
    );
}

/** Secondary sidebar panel: brand wordmark + a section's grouped menu + user. */
export function SidebarPanel({
    brand,
    homeUrl = '/main',
    section,
    sections,
    currentPath,
    user,
    showAllSections = false,
}: {
    brand?: OrbitBrand;
    homeUrl?: string;
    section?: MenuSection | null;
    sections?: MenuSection[];
    currentPath: string;
    user: SharedOrbit['user'];
    showAllSections?: boolean;
}) {
    return (
        <aside
            className="flex h-full w-60 shrink-0 flex-col"
            style={{
                backgroundColor: toneVar('nav-bg', '#ffffff'),
                borderRight: `1px solid ${toneVar('nav-border', '#e2e8f0')}`,
            }}
        >
            <BrandWordmark brand={brand} homeUrl={homeUrl} />

            <div className="flex-1 space-y-3 overflow-y-auto px-2 py-3">
                {showAllSections && sections
                    ? sections.map((entry) => (
                          <SectionNav key={entry.key} section={entry} currentPath={currentPath} />
                      ))
                    : <SectionNav section={section ?? null} currentPath={currentPath} />}
            </div>

            <div className="border-t p-3" style={{ borderColor: toneVar('nav-border', '#e2e8f0') }}>
                <UserMenu user={user} compact />
            </div>
        </aside>
    );
}

export function SidebarItem({ item, currentPath }: { item: OrbitMenuItem; currentPath: string }) {
    const t = useT();
    const hasChildren = (item.children?.length ?? 0) > 0;

    if (hasChildren) {
        return (
            <div className="pt-2">
                <p
                    className="px-3 pb-1 text-xs font-semibold uppercase tracking-wide"
                    style={{ color: toneVar('nav-muted', '#94a3b8') }}
                >
                    {t(item.label)}
                </p>
                <div className="space-y-0.5">
                    {item.children!.map((child, index) => (
                        <SidebarLink key={`${child.label}-${index}`} item={child} currentPath={currentPath} />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <>
            {item.divider ? (
                <div className="my-2 h-px" style={{ backgroundColor: toneVar('nav-border', '#e2e8f0') }} />
            ) : null}
            <SidebarLink item={item} currentPath={currentPath} />
        </>
    );
}

export function SidebarLink({ item, currentPath }: { item: OrbitMenuItem; currentPath: string }) {
    const t = useT();
    const active = pathMatches(toPath(item.url), currentPath);

    const content = (
        <>
            <span className="flex min-w-0 items-center gap-2.5">
                {item.icon ? <Icon name={item.icon} className="shrink-0 text-base" /> : null}
                <span className="truncate">{t(item.label)}</span>
            </span>
            {item.badge != null ? (
                <span
                    className="rounded-full px-2 text-xs"
                    style={{
                        backgroundColor: toneVar('nav-muted', '#f1f5f9'),
                        color: toneVar('secondary', '#0f172a'),
                    }}
                >
                    {item.badge}
                </span>
            ) : null}
        </>
    );

    if (!item.url) {
        return (
            <div
                className="px-3 py-2 text-xs font-semibold uppercase tracking-wide"
                style={{ color: toneVar('nav-muted', '#94a3b8') }}
            >
                {t(item.label)}
            </div>
        );
    }

    return (
        <Link
            href={item.url}
            className={cn(
                'flex items-center justify-between rounded-md px-3 py-2 text-sm',
                active ? 'font-medium' : '',
            )}
            style={{
                backgroundColor: active ? toneVar('nav-active-bg', 'rgba(23, 206, 145, 0.12)') : 'transparent',
                color: active ? toneVar('nav-active-fg', '#0f766e') : toneVar('secondary', '#475569'),
            }}
        >
            {content}
        </Link>
    );
}

export function UserMenu({ user, compact = false }: { user: SharedOrbit['user']; compact?: boolean }) {
    const t = useT();
    const name = user?.name ?? user?.email ?? null;

    const logout = () => {
        router.post('/logout');
    };

    if (compact) {
        return (
            <div className="flex items-center gap-2">
                <span
                    className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                    style={{
                        backgroundColor: toneVar('nav-active-bg', 'rgba(23, 206, 145, 0.12)'),
                        color: toneVar('primary', '#17ce91'),
                    }}
                >
                    {(name ?? '?').charAt(0).toUpperCase()}
                </span>
                <span
                    className="min-w-0 flex-1 truncate text-sm"
                    style={{ color: toneVar('secondary', '#0f172a') }}
                >
                    {name}
                </span>
                <button
                    type="button"
                    onClick={logout}
                    title={t('Log out')}
                    className="rounded-md p-1.5"
                    style={{ color: toneVar('nav-muted', '#94a3b8') }}
                >
                    <Icon name="bs.box-arrow-right" className="text-base" />
                </button>
            </div>
        );
    }

    return (
        <div className="flex items-center gap-2">
            <span className="hidden text-sm sm:inline" style={{ color: toneVar('secondary', '#475569') }}>
                {name}
            </span>
            <button
                type="button"
                onClick={logout}
                title={t('Log out')}
                className="rounded-md p-1.5"
                style={{ color: toneVar('nav-muted', '#94a3b8') }}
            >
                <Icon name="bs.box-arrow-right" className="text-base" />
            </button>
        </div>
    );
}

export function Breadcrumbs({ items }: { items?: Breadcrumb[] }) {
    if (!items || items.length === 0) {
        return <span />;
    }

    return (
        <nav className="flex items-center gap-1.5 truncate text-sm" style={{ color: toneVar('secondary', '#64748b') }}>
            {items.map((crumb, index) => (
                <span key={`${crumb.label}-${index}`} className="flex items-center gap-1.5">
                    {index > 0 ? <span style={{ color: toneVar('nav-border', '#cbd5e1') }}>/</span> : null}
                    {crumb.url ? (
                        <Link href={crumb.url}>
                            {crumb.label}
                        </Link>
                    ) : (
                        <span>{crumb.label}</span>
                    )}
                </span>
            ))}
        </nav>
    );
}

/** Right-aligned header actions shared across layouts. */
export function HeaderActions({
    user,
    darkMode,
}: {
    user: SharedOrbit['user'];
    darkMode?: DarkModeSetting | null;
}) {
    return (
        <div className="flex items-center gap-3">
            <ThemeModeSwitcher defaultMode={darkMode} />
            <LanguageSwitcher />
            <NotificationCenter />
            <UserMenu user={user} />
        </div>
    );
}

/** Mobile drawer toggle button (hidden on md+). */
export function MobileMenuButton({ onClick }: { onClick: () => void }) {
    const t = useT();

    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-md p-1.5 md:hidden"
            style={{ color: toneVar('secondary', '#64748b') }}
            aria-label={t('Toggle menu')}
        >
            <Icon name="bs.list" className="text-xl" />
        </button>
    );
}

/** The main content region with the page title/description/actions header. */
export function PageBody({ title, description, actions, children }: DashboardContentProps) {
    return (
        <main className="flex-1 p-4 md:p-6" style={{ backgroundColor: toneVar('page-bg', '#f8fafc') }}>
            <div className="mx-auto max-w-6xl">
                {title || actions ? (
                    <div className="mb-5 flex flex-wrap items-start justify-between gap-4">
                        <div className="min-w-0">
                            {title ? (
                                <h1 className="truncate text-xl font-semibold" style={{ color: toneVar('secondary', '#0f172a') }}>
                                    {title}
                                </h1>
                            ) : null}
                            {description ? (
                                <p className="mt-1 text-sm" style={{ color: toneVar('secondary', '#64748b') }}>
                                    {description}
                                </p>
                            ) : null}
                        </div>
                        {actions ? <div className="shrink-0">{actions}</div> : null}
                    </div>
                ) : null}

                {children}
            </div>
        </main>
    );
}
