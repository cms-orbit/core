import { Link, router } from '@inertiajs/react';
import { logout as orbitLogout } from '@/routes/orbit';
import { profile as orbitProfile } from '@/routes/orbit';
import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '../contract';
import { cn } from '../lib/cn';
import type { OrbitI18n } from '../lib/i18n';
import { useT } from '../lib/i18n';
import {
    normalizeContentWidth,
    resolveBrandAsset,
    type ContentWidthOption,
    type DarkModeSetting,
    type OrbitBrand,
} from '../theme/branding';
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
    url?: string | null;
    placement?: {
        rail?: 'top' | 'bottom';
        sidebar?: 'top' | 'bottom';
        topbar?: 'left' | 'right';
    };
}

export interface SharedOrbit {
    menu?: OrbitMenuItem[];
    sections?: Record<string, OrbitMenuSectionRegistry>;
    permissions?: string[];
    home?: string | null;
    user?: {
        id: number | string;
        name?: string;
        email?: string;
        avatarUrl?: string | null;
    } | null;
    flash?: { message?: string | null; type?: string | null };
    brand?: OrbitBrand;
    i18n?: OrbitI18n;
}

export interface OrbitSectionPlacement {
    rail?: 'top' | 'bottom';
    sidebar?: 'top' | 'bottom';
    topbar?: 'left' | 'right';
}

export interface MenuSection {
    key: string;
    label: string;
    icon: string;
    sort: number;
    url?: string | null;
    placement: OrbitSectionPlacement;
    items: OrbitMenuItem[];
}

/** Content props shared by every layout mode. */
export interface DashboardContentProps {
    title?: string | null;
    description?: string | null;
    breadcrumbs?: Breadcrumb[];
    actions?: ReactNode;
    contentWidth?: ContentWidthOption | null;
    children: ReactNode;
}

/** Props a concrete layout view receives from the dispatcher. */
export interface LayoutViewProps extends DashboardContentProps {
    orbit: SharedOrbit;
    brand?: OrbitBrand;
    currentPath: string;
}

export const UNSECTIONED = '__general__';
const SINGLE_SIDEBAR_SECTION_STORAGE_KEY = 'orbit.single-sidebar.sections';

function toneVar(name: string, fallback: string): string {
    return `var(--color-orbit-${name}, ${fallback})`;
}

function contentWidthClass(contentWidth: ContentWidthOption | null | undefined): string {
    switch (normalizeContentWidth(contentWidth)) {
        case 'full':
            return 'w-full';
        case 'wide':
            return 'mx-auto w-full max-w-7xl';
        case 'xwide':
            return 'mx-auto w-full max-w-screen-2xl';
        case 'default':
        default:
            return 'mx-auto w-full max-w-6xl';
    }
}

export function ContentContainer({
    children,
    contentWidth = 'default',
    className,
}: {
    children: ReactNode;
    contentWidth?: ContentWidthOption | null;
    className?: string;
}) {
    return <div className={cn(contentWidthClass(contentWidth), className)}>{children}</div>;
}

/** Section name → rail/nav glyph. Falls back to the first item's icon. */
export const SECTION_ICONS: Record<string, string> = {
    'Access Control': 'bs.shield-lock',
    'Users & Roles': 'bs.people-fill',
    '사용자 및 역할': 'bs.people-fill',
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
                url: registered?.url ?? null,
                placement: registered?.placement ?? {},
                items: [],
            });
        }

        const section = map.get(key)!;
        section.sort = Math.min(section.sort, registered?.sort ?? item.sort ?? index);
        section.url = registered?.url ?? section.url ?? null;
        section.placement = registered?.placement ?? section.placement;
        section.items.push(item);
    });

    return Array.from(map.values()).sort((a, b) => a.sort - b.sort);
}

export function getSectionDirectUrl(section: MenuSection | null): string | null {
    if (!section?.url || section.items.length !== 1) {
        return null;
    }

    const [item] = section.items;

    if ((item.children?.length ?? 0) > 0) {
        return null;
    }

    return toPath(item.url) === toPath(section.url) ? section.url : null;
}

export function getSectionNavItems(section: MenuSection | null): OrbitMenuItem[] {
    if (!section) {
        return [];
    }

    return getSectionDirectUrl(section) ? [] : section.items;
}

function countSectionNavEntries(items: OrbitMenuItem[]): number {
    return items.reduce((count, item) => {
        const childCount = item.children?.length ?? 0;

        return count + (childCount > 0 ? childCount : 1);
    }, 0);
}

export function hasSectionSidebarContent(section: MenuSection | null): boolean {
    return countSectionNavEntries(getSectionNavItems(section)) > 0;
}

function makeSectionLinkItem(section: MenuSection): OrbitMenuItem | null {
    const url = getSectionDirectUrl(section);

    if (!url) {
        return null;
    }

    return {
        label: section.label,
        icon: section.icon,
        url,
    };
}

export function splitSectionsByPlacement(
    sections: MenuSection[],
    surface: 'rail' | 'sidebar' | 'topbar',
): {
    primary: MenuSection[];
    secondary: MenuSection[];
} {
    return sections.reduce(
        (groups, section) => {
            const isSecondary =
                surface === 'topbar'
                    ? section.placement.topbar === 'right'
                    : section.placement[surface] === 'bottom';

            groups[isSecondary ? 'secondary' : 'primary'].push(section);

            return groups;
        },
        { primary: [] as MenuSection[], secondary: [] as MenuSection[] },
    );
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
    const symbolUrl = resolveBrandAsset(brand, 'symbol');
    const { primary, secondary } = splitSectionsByPlacement(sections, 'rail');

    const renderSections = (entries: MenuSection[]) =>
        entries.map((section) => {
            const url = getSectionDirectUrl(section);
            const isSelected = section.key === selectedKey;
            const isActive = section.key === activeKey;
            const className = cn(
                'group relative flex h-11 w-11 items-center justify-center rounded-2xl transition hover:rounded-xl',
            );
            const style = {
                backgroundColor: isSelected ? toneVar('rail-active-bg', '#d1fae5') : 'transparent',
                color: isSelected ? toneVar('rail-active-fg', '#ffffff') : toneVar('rail-icon', '#64748b'),
            };
            const marker = (
                <span
                    className={cn(
                        'absolute -left-3 w-1 rounded-r-full transition-all',
                        isActive || isSelected ? 'opacity-100' : 'opacity-0',
                        isSelected ? 'h-8' : 'h-5',
                    )}
                    style={{ backgroundColor: toneVar('primary', '#10b981') }}
                />
            );
            const icon = <Icon name={section.icon} className="text-xl" />;

            if (url) {
                return (
                    <Link key={section.key} href={url} title={t(section.label)} className={className} style={style}>
                        {marker}
                        {icon}
                    </Link>
                );
            }

            return (
                <button
                    key={section.key}
                    type="button"
                    onClick={() => onSelect(section.key)}
                    title={t(section.label)}
                    className={className}
                    style={style}
                >
                    {marker}
                    {icon}
                </button>
            );
        });

    return (
        <aside
            className={cn(
                'sticky top-0 flex h-screen w-16 shrink-0 self-start flex-col items-center gap-1 overflow-hidden py-3',
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
                style={{ backgroundColor: toneVar('rail-symbol-bg', '#17ce91') }}
            >
                {symbolUrl ? (
                    <img src={symbolUrl} alt={brand?.name ?? 'Orbit'} className="h-6 w-6 object-contain" />
                ) : (
                    <span className="text-sm font-bold" style={{ color: toneVar('primary', '#10b981') }}>
                        {(brand?.name ?? 'O').charAt(0)}
                    </span>
                )}
            </Link>

            <div className="mb-1 h-px w-8" style={{ backgroundColor: toneVar('rail-border', '#e2e8f0') }} />

            <nav className="flex min-h-0 flex-1 flex-col items-center self-stretch overflow-y-auto">
                <div className="flex w-full flex-1 flex-col items-center gap-1">{renderSections(primary)}</div>
                {secondary.length > 0 ? (
                    <div className="flex w-full flex-col items-center gap-1 pt-2">{renderSections(secondary)}</div>
                ) : null}
            </nav>
        </aside>
    );
}

/** Brand wordmark row used at the top of sidebars. */
export function BrandWordmark({ brand, homeUrl = '/main' }: { brand?: OrbitBrand; homeUrl?: string }) {
    const logoUrl = resolveBrandAsset(brand, 'logo');

    return (
        <div
            className="flex h-14 items-center gap-2 border-b px-4"
            style={{ borderColor: toneVar('nav-border', '#e2e8f0') }}
        >
            <Link href={homeUrl} className="flex items-center gap-2" title={brand?.name ?? 'Orbit'}>
                {logoUrl ? (
                    <img src={logoUrl} alt={brand?.name ?? 'Orbit'} className="h-5 w-auto object-contain" />
                ) : (
                    <span className="text-lg font-semibold" style={{ color: toneVar('secondary', '#0f172a') }}>
                        {brand?.name ?? 'Orbit'}
                    </span>
                )}
            </Link>
        </div>
    );
}

function SubtleSectionHeader({ section, currentPath }: { section: MenuSection; currentPath: string }) {
    const t = useT();
    const directItem = makeSectionLinkItem(section);

    return (
        <div className="mb-3 px-3 pt-2">
            <div
                className="mb-2 h-px w-full"
                style={{ backgroundColor: toneVar('nav-section-border', '#e2e8f0') }}
            />
            {directItem ? (
                <SidebarLink item={directItem} currentPath={currentPath} />
            ) : (
                <div className="flex items-center gap-2">
                    <span
                        className="h-1.5 w-4 shrink-0 rounded-full"
                        style={{ backgroundColor: toneVar('primary', '#10b981'), opacity: 0.7 }}
                    />
                    <p
                        className="min-w-0 truncate text-[10px] font-semibold uppercase tracking-[0.18em]"
                        style={{ color: toneVar('nav-section-fg', '#334155') }}
                    >
                        {t(section.label)}
                    </p>
                </div>
            )}
        </div>
    );
}

function DetailedSectionHeader({
    section,
    currentPath,
    collapsed,
    onToggle,
}: {
    section: MenuSection;
    currentPath: string;
    collapsed?: boolean;
    onToggle?: () => void;
}) {
    const t = useT();
    const directUrl = getSectionDirectUrl(section);
    const active = pathMatches(toPath(directUrl), currentPath);
    const className = 'mb-2 flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left transition';
    const style = {
        backgroundColor: active ? toneVar('nav-active-bg', '#d1fae5') : 'transparent',
        color: active ? toneVar('nav-active-fg', '#047857') : toneVar('nav-section-fg', '#334155'),
    };
    const content = (
        <>
            <span
                className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                style={{
                    backgroundColor: toneVar('nav-section-bg', '#f8fafc'),
                    border: `1px solid ${toneVar('nav-section-border', '#e2e8f0')}`,
                    color: toneVar('primary', '#10b981'),
                }}
            >
                <Icon name={section.icon} className="text-sm" />
            </span>
            <p
                className="min-w-0 flex-1 truncate text-sm font-semibold"
                style={{ color: active ? toneVar('nav-active-fg', '#047857') : toneVar('nav-section-fg', '#334155') }}
            >
                {t(section.label)}
            </p>
            {onToggle && !directUrl ? (
                <Icon name={collapsed ? 'bs.chevron-right' : 'bs.chevron-down'} className="text-sm" />
            ) : null}
        </>
    );

    if (directUrl) {
        return (
            <Link href={directUrl} className={className} style={style}>
                {content}
            </Link>
        );
    }

    return (
        <button
            type="button"
            onClick={onToggle}
            className={className}
            style={style}
            aria-expanded={collapsed === undefined ? undefined : !collapsed}
            aria-label={t(collapsed ? 'Expand section' : 'Collapse section')}
        >
            {content}
        </button>
    );
}

/** A single section's grouped links (header + items). */
export function SectionNav({
    section,
    currentPath,
    headerStyle = 'subtle',
    collapsible = false,
    collapsed = false,
    onToggle,
}: {
    section: MenuSection | null;
    currentPath: string;
    headerStyle?: 'hidden' | 'subtle' | 'detailed';
    collapsible?: boolean;
    collapsed?: boolean;
    onToggle?: () => void;
}) {
    const t = useT();
    const items = getSectionNavItems(section);

    if (!section) {
        return (
            <p className="px-3 py-2 text-sm" style={{ color: toneVar('nav-muted', '#94a3b8') }}>
                {t('No menu items.')}
            </p>
        );
    }

    return (
        <>
            {headerStyle === 'subtle' ? <SubtleSectionHeader section={section} currentPath={currentPath} /> : null}
            {headerStyle === 'detailed' ? (
                <DetailedSectionHeader
                    section={section}
                    currentPath={currentPath}
                    collapsed={collapsible ? collapsed : undefined}
                    onToggle={collapsible ? onToggle : undefined}
                />
            ) : null}
            {!collapsed && items.length > 0 ? (
                <nav className="space-y-0.5">
                    {items.map((item, index) => (
                        <SidebarItem key={`${item.label}-${index}`} item={item} currentPath={currentPath} />
                    ))}
                </nav>
            ) : null}
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
    sectionHeaderStyle = 'subtle',
    collapsibleSections = false,
}: {
    brand?: OrbitBrand;
    homeUrl?: string;
    section?: MenuSection | null;
    sections?: MenuSection[];
    currentPath: string;
    user: SharedOrbit['user'];
    showAllSections?: boolean;
    sectionHeaderStyle?: 'hidden' | 'subtle' | 'detailed';
    collapsibleSections?: boolean;
}) {
    const [collapsedSections, setCollapsedSections] = useState<Record<string, boolean>>({});
    const groupedSections = useMemo(
        () => splitSectionsByPlacement(sections ?? [], 'sidebar'),
        [sections],
    );
    const shouldRenderSectionPanel = showAllSections || hasSectionSidebarContent(section ?? null);

    useEffect(() => {
        if (!collapsibleSections || typeof window === 'undefined') {
            return;
        }

        try {
            const stored = window.localStorage.getItem(SINGLE_SIDEBAR_SECTION_STORAGE_KEY);

            if (stored) {
                setCollapsedSections(JSON.parse(stored) as Record<string, boolean>);
            }
        } catch {
            // Ignore malformed client state and keep defaults.
        }
    }, [collapsibleSections]);

    useEffect(() => {
        if (!collapsibleSections || typeof window === 'undefined') {
            return;
        }

        window.localStorage.setItem(
            SINGLE_SIDEBAR_SECTION_STORAGE_KEY,
            JSON.stringify(collapsedSections),
        );
    }, [collapsedSections, collapsibleSections]);

    const toggleSection = (key: string) => {
        setCollapsedSections((current) => ({
            ...current,
            [key]: !current[key],
        }));
    };

    if (!shouldRenderSectionPanel) {
        return null;
    }

    return (
        <aside
            className="sticky top-0 flex h-screen w-60 shrink-0 self-start flex-col"
            style={{
                backgroundColor: toneVar('nav-bg', '#ffffff'),
                borderRight: `1px solid ${toneVar('nav-border', '#e2e8f0')}`,
            }}
        >
            <BrandWordmark brand={brand} homeUrl={homeUrl} />

            <div className="flex min-h-0 flex-1 flex-col overflow-y-auto px-2 py-3">
                {showAllSections && sections ? (
                    <div className="flex min-h-0 flex-1 flex-col">
                        <div className="flex-1 space-y-3">
                            {groupedSections.primary.map((entry) => (
                                <SectionNav
                                    key={entry.key}
                                    section={entry}
                                    currentPath={currentPath}
                                    headerStyle={sectionHeaderStyle}
                                    collapsible={collapsibleSections}
                                    collapsed={collapsedSections[entry.key] ?? false}
                                    onToggle={() => toggleSection(entry.key)}
                                />
                            ))}
                        </div>
                        {groupedSections.secondary.length > 0 ? (
                            <div
                                className="mt-3 space-y-3 border-t pt-3"
                                style={{ borderColor: toneVar('nav-border', '#e2e8f0') }}
                            >
                                {groupedSections.secondary.map((entry) => (
                                    <SectionNav
                                        key={entry.key}
                                        section={entry}
                                        currentPath={currentPath}
                                        headerStyle={sectionHeaderStyle}
                                        collapsible={collapsibleSections}
                                        collapsed={collapsedSections[entry.key] ?? false}
                                        onToggle={() => toggleSection(entry.key)}
                                    />
                                ))}
                            </div>
                        ) : null}
                    </div>
                ) : (
                    <SectionNav
                        section={section ?? null}
                        currentPath={currentPath}
                        headerStyle={sectionHeaderStyle}
                    />
                )}
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
                    className="px-3 pb-1 text-[11px] font-semibold tracking-wide"
                    style={{ color: toneVar('nav-group-fg', '#475569') }}
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
                backgroundColor: active ? toneVar('nav-active-bg', '#d1fae5') : 'transparent',
                color: active ? toneVar('nav-active-fg', '#047857') : toneVar('secondary', '#475569'),
            }}
        >
            {content}
        </Link>
    );
}

function AvatarBadge({
    user,
    size = 'md',
}: {
    user: SharedOrbit['user'];
    size?: 'sm' | 'md';
}) {
    const name = user?.name ?? user?.email ?? '?';
    const sizeClass = size === 'sm' ? 'h-8 w-8 text-sm' : 'h-9 w-9 text-sm';

    if (user?.avatarUrl) {
        return <img src={user.avatarUrl} alt="" className={cn('shrink-0 rounded-full object-cover', sizeClass)} />;
    }

    return (
        <span
            className={cn('flex shrink-0 items-center justify-center rounded-full font-semibold', sizeClass)}
            style={{
                backgroundColor: toneVar('nav-active-bg', '#d1fae5'),
                color: toneVar('primary', '#10b981'),
            }}
            aria-hidden
        >
            {name.charAt(0).toUpperCase()}
        </span>
    );
}

export function UserMenu({ user, compact = false }: { user: SharedOrbit['user']; compact?: boolean }) {
    const t = useT();
    const name = user?.name ?? user?.email ?? null;
    const profileUrl = user ? orbitProfile.url() : null;

    const logout = () => {
        router.post(orbitLogout.url());
    };

    if (compact) {
        return (
            <div className="flex items-center gap-2">
                {profileUrl ? (
                    <Link
                        href={profileUrl}
                        className="rounded-full"
                        aria-label={t('Edit profile')}
                        title={t('Edit profile')}
                    >
                        <AvatarBadge user={user} size="sm" />
                    </Link>
                ) : (
                    <AvatarBadge user={user} size="sm" />
                )}
                {profileUrl ? (
                    <Link
                        href={profileUrl}
                        className="min-w-0 flex-1 truncate text-sm"
                        style={{ color: toneVar('secondary', '#0f172a') }}
                    >
                        {name}
                    </Link>
                ) : (
                    <span
                        className="min-w-0 flex-1 truncate text-sm"
                        style={{ color: toneVar('secondary', '#0f172a') }}
                    >
                        {name}
                    </span>
                )}
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
            {profileUrl ? (
                <Link
                    href={profileUrl}
                    className="flex items-center gap-2 rounded-full"
                    aria-label={t('Edit profile')}
                    title={t('Edit profile')}
                >
                    <AvatarBadge user={user} />
                    <span className="hidden text-sm sm:inline" style={{ color: toneVar('secondary', '#475569') }}>
                        {name}
                    </span>
                </Link>
            ) : (
                <>
                    <AvatarBadge user={user} />
                    <span className="hidden text-sm sm:inline" style={{ color: toneVar('secondary', '#475569') }}>
                        {name}
                    </span>
                </>
            )}
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
    themeMode,
    themeToggleEnabled,
    showUserMenu = true,
}: {
    user: SharedOrbit['user'];
    themeMode?: DarkModeSetting | null;
    themeToggleEnabled?: boolean | null;
    showUserMenu?: boolean;
}) {
    return (
        <div className="flex items-center gap-3">
            {themeToggleEnabled !== false ? <ThemeModeSwitcher defaultMode={themeMode} /> : null}
            <LanguageSwitcher />
            <NotificationCenter />
            {showUserMenu ? <UserMenu user={user} /> : null}
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
export function PageBody({ title, description, actions, children, contentWidth = 'contained' }: DashboardContentProps) {
    return (
        <main className="flex-1 p-4 md:p-6" style={{ backgroundColor: toneVar('page-bg', '#f8fafc') }}>
            <ContentContainer contentWidth={contentWidth}>
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
            </ContentContainer>
        </main>
    );
}
