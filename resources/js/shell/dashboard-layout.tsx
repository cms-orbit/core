import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { Breadcrumb } from '../contract';
import { resolveShellLayout } from '../registry';
import type { ContentWidthOption, LayoutMode } from '../theme/branding';
import { useBrandTheme } from '../theme/branding';
import { OrbitProviders } from '../ui/providers';
import { HybridLayoutView } from './layouts/hybrid';
import { PaletteSplitLayoutView } from './layouts/palette-split';
import { SingleLayoutView } from './layouts/single';
import { TopbarLayoutView } from './layouts/topbar';
import { PageBody, type LayoutViewProps, type SharedOrbit } from './parts';

export type { OrbitMenuItem, SharedOrbit } from './parts';

interface PageProps {
    orbit?: SharedOrbit;
    [key: string]: unknown;
}

/** Layout mode → view component. Falls back to the palette split sidebar. */
const LAYOUT_VIEWS: Record<LayoutMode, (props: LayoutViewProps) => ReactNode> = {
    'palette-split': PaletteSplitLayoutView,
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
    chrome = 'default',
    contentWidth,
    breadcrumbs = [],
    actions,
    children,
}: {
    title?: string | null;
    description?: string | null;
    chrome?: 'default' | 'none' | null;
    contentWidth?: ContentWidthOption | null;
    breadcrumbs?: Breadcrumb[];
    actions?: ReactNode;
    children: ReactNode;
}) {
    const page = usePage<PageProps>();
    const orbit = page.props.orbit ?? {};
    const brand = orbit.brand;
    const brandStyle = useBrandTheme(brand);
    const currentPath = (page.url || '/').split('?')[0];

    const mode = (brand?.layout ?? 'palette-split') as LayoutMode;
    const LayoutView =
        resolveShellLayout(mode) ?? LAYOUT_VIEWS[mode as keyof typeof LAYOUT_VIEWS] ?? PaletteSplitLayoutView;

    if (chrome === 'none') {
        return (
            <OrbitProviders>
                <div style={brandStyle} className="min-h-screen">
                    <PageBody
                        title={title}
                        description={description}
                        actions={actions}
                        contentWidth={contentWidth ?? brand?.contentWidth}
                    >
                        {children}
                    </PageBody>
                </div>
            </OrbitProviders>
        );
    }

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
                    contentWidth={contentWidth}
                >
                    {children}
                </LayoutView>
            </div>
        </OrbitProviders>
    );
}
