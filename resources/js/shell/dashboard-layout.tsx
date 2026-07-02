import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '../contract';
import type { LayoutMode } from '../theme/branding';
import { useBrandTheme } from '../theme/branding';
import { OrbitProviders } from '../ui/providers';
import { HybridLayoutView } from './layouts/hybrid';
import { SingleLayoutView } from './layouts/single';
import { SplitLayoutView } from './layouts/split';
import { TopbarLayoutView } from './layouts/topbar';
import type { LayoutViewProps, SharedOrbit } from './parts';

export type { OrbitMenuItem, SharedOrbit } from './parts';

interface PageProps {
    orbit?: SharedOrbit;
    [key: string]: unknown;
}

/** Layout mode → view component. Falls back to the split sidebar. */
const LAYOUT_VIEWS: Record<LayoutMode, (props: LayoutViewProps) => ReactNode> = {
    'sidebar-split': SplitLayoutView,
    'sidebar-single': SingleLayoutView,
    topbar: TopbarLayoutView,
    hybrid: HybridLayoutView,
};

/**
 * Admin shell dispatcher. Resolves the configured layout mode from the shared
 * `orbit.brand.layout` prop and renders the matching layout view, injecting the
 * per-mode brand theme (CSS variables + dark mode) on the root.
 */
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
    const brand = orbit.brand;
    const brandStyle = useBrandTheme(brand);
    const currentPath = (page.url || '/').split('?')[0];

    const mode = (brand?.layout ?? 'sidebar-split') as LayoutMode;
    const LayoutView = LAYOUT_VIEWS[mode] ?? SplitLayoutView;

    return (
        <OrbitProviders>
            <div style={brandStyle}>
                <LayoutView
                    orbit={orbit}
                    brand={brand}
                    currentPath={currentPath}
                    title={title}
                    description={description}
                    breadcrumbs={breadcrumbs}
                    actions={actions}
                >
                    {children}
                </LayoutView>
            </div>
        </OrbitProviders>
    );
}
