import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '../contract';
import { cn } from '../lib/cn';
import type { OrbitBrand } from '../theme/branding';
import { useBrandTheme } from '../theme/branding';
import { Icon } from '../ui/icon';
import { NotificationCenter } from '../ui/notification';
import { OrbitProviders } from '../ui/providers';

export interface OrbitMenuItem {
    label: string;
    url?: string | null;
    icon?: string | null;
    badge?: string | number | null;
    section?: string | null;
    sort?: number;
    divider?: boolean;
    active?: boolean;
    children?: OrbitMenuItem[];
}

export interface SharedOrbit {
    menu?: OrbitMenuItem[];
    permissions?: string[];
    user?: { id: number | string; name?: string; email?: string } | null;
    flash?: { message?: string | null; type?: string | null };
    brand?: OrbitBrand;
}

interface PageProps {
    orbit?: SharedOrbit;
    [key: string]: unknown;
}

interface MenuSection {
    key: string;
    label: string;
    icon: string;
    sort: number;
    items: OrbitMenuItem[];
}

const UNSECTIONED = '__general__';

/** Section name → icon rail glyph. Falls back to the first item's icon. */
const SECTION_ICONS: Record<string, string> = {
    'Access Control': 'bs.shield-lock',
    System: 'bs.gear',
    Settings: 'bs.gear',
    Entities: 'bs.grid',
    Content: 'bs.layers',
    Media: 'bs.images',
};

/** Strip scheme + host so absolute route URLs compare cleanly to the SPA path. */
function toPath(url?: string | null): string | null {
    if (!url) {
        return null;
    }

    return url.replace(/^https?:\/\/[^/]+/i, '') || '/';
}

function pathMatches(itemPath: string | null, currentPath: string): boolean {
    if (!itemPath) {
        return false;
    }

    if (itemPath === '/') {
        return currentPath === '/';
    }

    return currentPath === itemPath || currentPath.startsWith(`${itemPath}/`);
}

function itemTreeActive(item: OrbitMenuItem, currentPath: string): boolean {
    if (pathMatches(toPath(item.url), currentPath)) {
        return true;
    }

    return (item.children ?? []).some((child) => itemTreeActive(child, currentPath));
}

/** Group the flat, pre-sorted menu into sections for the icon rail + sidebar. */
function buildSections(menu: OrbitMenuItem[]): MenuSection[] {
    const map = new Map<string, MenuSection>();

    menu.forEach((item, index) => {
        const label = item.section?.trim() || '';
        const key = label || UNSECTIONED;

        if (!map.has(key)) {
            map.set(key, {
                key,
                label: label || 'General',
                icon: SECTION_ICONS[label] ?? item.icon ?? 'bs.grid',
                sort: item.sort ?? index,
                items: [],
            });
        }

        const section = map.get(key)!;
        section.sort = Math.min(section.sort, item.sort ?? index);
        section.items.push(item);
    });

    return Array.from(map.values()).sort((a, b) => a.sort - b.sort);
}

export function DashboardLayout({
    title,
    description,
    breadcrumbs = [],
    actions,
    children,
}: {
    title?: string | null;
    description?: string | null;
    breadcrumbs?: Breadcrumb[];
    actions?: ReactNode;
    children: ReactNode;
}) {
    const page = usePage<PageProps>();
    const orbit = page.props.orbit ?? {};
    const menu = orbit.menu ?? [];
    const brand = orbit.brand;
    const brandStyle = useBrandTheme(brand);
    const currentPath = (page.url || '/').split('?')[0];

    const sections = useMemo(() => buildSections(menu), [menu]);

    const activeKey = useMemo(() => {
        const match = sections.find((section) =>
            section.items.some((item) => itemTreeActive(item, currentPath)),
        );

        return match?.key ?? sections[0]?.key ?? null;
    }, [sections, currentPath]);

    const [selectedKey, setSelectedKey] = useState<string | null>(activeKey);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        if (activeKey) {
            setSelectedKey(activeKey);
        }
    }, [activeKey]);

    useEffect(() => {
        setMobileOpen(false);
    }, [currentPath]);

    const selectedSection =
        sections.find((section) => section.key === selectedKey) ?? sections[0] ?? null;

    return (
        <OrbitProviders>
            <div
                style={brandStyle}
                className="flex min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
            >
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
                            <button
                                type="button"
                                onClick={() => setMobileOpen((open) => !open)}
                                className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 md:hidden dark:hover:bg-gray-800"
                                aria-label="Toggle menu"
                            >
                                <Icon name="bs.list" className="text-xl" />
                            </button>
                            <Breadcrumbs items={breadcrumbs} />
                        </div>
                        <div className="flex items-center gap-3">
                            <NotificationCenter />
                            <UserMenu user={orbit.user ?? null} />
                        </div>
                    </header>

                    <main className="flex-1 p-4 md:p-6">
                        <div className="mx-auto max-w-6xl">
                            {(title || actions) && (
                                <div className="mb-5 flex flex-wrap items-start justify-between gap-4">
                                    <div className="min-w-0">
                                        {title ? (
                                            <h1 className="truncate text-xl font-semibold">{title}</h1>
                                        ) : null}
                                        {description ? (
                                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {description}
                                            </p>
                                        ) : null}
                                    </div>
                                    {actions ? <div className="shrink-0">{actions}</div> : null}
                                </div>
                            )}

                            {children}
                        </div>
                    </main>
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
        </OrbitProviders>
    );
}

/** Discord-style primary icon rail: brand mark, section icons, spacer. */
function IconRail({
    brand,
    sections,
    selectedKey,
    activeKey,
    onSelect,
    variant = 'desktop',
}: {
    brand?: OrbitBrand;
    sections: MenuSection[];
    selectedKey: string | null;
    activeKey: string | null;
    onSelect: (key: string) => void;
    variant?: 'desktop' | 'mobile';
}) {
    return (
        <aside
            className={cn(
                'w-16 shrink-0 flex-col items-center gap-1 border-r border-gray-200 bg-white py-3 dark:border-gray-800 dark:bg-gray-950',
                variant === 'mobile' ? 'flex' : 'hidden md:flex',
            )}
        >
            <Link
                href="/"
                className="mb-2 flex h-11 w-11 items-center justify-center rounded-2xl bg-orbit-primary/10 transition hover:rounded-xl"
                title={brand?.name ?? 'Orbit'}
            >
                {brand?.symbol ? (
                    <img src={brand.symbol} alt={brand?.name ?? 'Orbit'} className="h-6 w-6" />
                ) : (
                    <span className="text-sm font-bold text-orbit-primary">
                        {(brand?.name ?? 'O').charAt(0)}
                    </span>
                )}
            </Link>

            <div className="mb-1 h-px w-8 bg-gray-200 dark:bg-gray-800" />

            <nav className="flex flex-1 flex-col items-center gap-1">
                {sections.map((section) => {
                    const isSelected = section.key === selectedKey;
                    const isActive = section.key === activeKey;

                    return (
                        <button
                            key={section.key}
                            type="button"
                            onClick={() => onSelect(section.key)}
                            title={section.label}
                            className={cn(
                                'group relative flex h-11 w-11 items-center justify-center rounded-2xl transition hover:rounded-xl',
                                isSelected
                                    ? 'bg-orbit-primary text-white'
                                    : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',
                            )}
                        >
                            <span
                                className={cn(
                                    'absolute -left-3 h-6 w-1 rounded-r-full bg-orbit-primary transition-all',
                                    isActive || isSelected ? 'opacity-100' : 'opacity-0',
                                    isSelected ? 'h-8' : 'h-5',
                                )}
                            />
                            <Icon name={section.icon} className="text-xl" />
                        </button>
                    );
                })}
            </nav>
        </aside>
    );
}

/** Secondary sidebar panel: brand wordmark + the selected section's grouped menu. */
function SidebarPanel({
    brand,
    section,
    currentPath,
    user,
}: {
    brand?: OrbitBrand;
    section: MenuSection | null;
    currentPath: string;
    user: SharedOrbit['user'];
}) {
    return (
        <aside className="flex h-full w-60 shrink-0 flex-col border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div className="flex h-14 items-center gap-2 border-b border-gray-200 px-4 dark:border-gray-800">
                {brand?.logo ? (
                    <img src={brand.logo} alt={brand?.name ?? 'Orbit'} className="h-5 w-auto" />
                ) : (
                    <span className="text-lg font-semibold">{brand?.name ?? 'Orbit'}</span>
                )}
            </div>

            <div className="flex-1 overflow-y-auto px-2 py-3">
                {section ? (
                    <>
                        <p className="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            {section.label}
                        </p>
                        <nav className="space-y-0.5">
                            {section.items.map((item, index) => (
                                <SidebarItem
                                    key={`${item.label}-${index}`}
                                    item={item}
                                    currentPath={currentPath}
                                />
                            ))}
                        </nav>
                    </>
                ) : (
                    <p className="px-3 py-2 text-sm text-gray-400">No menu items.</p>
                )}
            </div>

            <div className="border-t border-gray-200 p-3 dark:border-gray-800">
                <UserMenu user={user} compact />
            </div>
        </aside>
    );
}

function SidebarItem({ item, currentPath }: { item: OrbitMenuItem; currentPath: string }) {
    const hasChildren = (item.children?.length ?? 0) > 0;

    if (hasChildren) {
        return (
            <div className="pt-2">
                <p className="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    {item.label}
                </p>
                <div className="space-y-0.5">
                    {item.children!.map((child, index) => (
                        <SidebarLink
                            key={`${child.label}-${index}`}
                            item={child}
                            currentPath={currentPath}
                        />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <>
            {item.divider ? <div className="my-2 h-px bg-gray-100 dark:bg-gray-800" /> : null}
            <SidebarLink item={item} currentPath={currentPath} />
        </>
    );
}

function SidebarLink({ item, currentPath }: { item: OrbitMenuItem; currentPath: string }) {
    const active = pathMatches(toPath(item.url), currentPath);

    const content = (
        <>
            <span className="flex min-w-0 items-center gap-2.5">
                {item.icon ? <Icon name={item.icon} className="shrink-0 text-base" /> : null}
                <span className="truncate">{item.label}</span>
            </span>
            {item.badge != null ? (
                <span className="rounded-full bg-gray-200 px-2 text-xs dark:bg-gray-700">
                    {item.badge}
                </span>
            ) : null}
        </>
    );

    if (!item.url) {
        return (
            <div className="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                {item.label}
            </div>
        );
    }

    return (
        <Link
            href={item.url}
            className={cn(
                'flex items-center justify-between rounded-md px-3 py-2 text-sm',
                active
                    ? 'bg-orbit-primary/10 font-medium text-orbit-primary'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
            )}
        >
            {content}
        </Link>
    );
}

function UserMenu({ user, compact = false }: { user: SharedOrbit['user']; compact?: boolean }) {
    const name = user?.name ?? user?.email ?? null;

    const logout = () => {
        router.post('/logout');
    };

    if (compact) {
        return (
            <div className="flex items-center gap-2">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orbit-primary/10 text-sm font-semibold text-orbit-primary">
                    {(name ?? '?').charAt(0).toUpperCase()}
                </span>
                <span className="min-w-0 flex-1 truncate text-sm text-gray-700 dark:text-gray-200">
                    {name}
                </span>
                <button
                    type="button"
                    onClick={logout}
                    title="Log out"
                    className="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
                >
                    <Icon name="bs.box-arrow-right" className="text-base" />
                </button>
            </div>
        );
    }

    return (
        <div className="flex items-center gap-2">
            <span className="hidden text-sm text-gray-500 sm:inline dark:text-gray-400">{name}</span>
            <button
                type="button"
                onClick={logout}
                title="Log out"
                className="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
            >
                <Icon name="bs.box-arrow-right" className="text-base" />
            </button>
        </div>
    );
}

function Breadcrumbs({ items }: { items: Breadcrumb[] }) {
    if (!items || items.length === 0) {
        return <span />;
    }

    return (
        <nav className="flex items-center gap-1.5 truncate text-sm text-gray-500 dark:text-gray-400">
            {items.map((crumb, index) => (
                <span key={`${crumb.label}-${index}`} className="flex items-center gap-1.5">
                    {index > 0 ? <span className="text-gray-300">/</span> : null}
                    {crumb.url ? (
                        <Link href={crumb.url} className="hover:text-gray-700 dark:hover:text-gray-200">
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
