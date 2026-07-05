/**
 * TypeScript mirror of the Orbit serialize → React JSON contract.
 * Keep in sync with ../../CONTRACT.md and the PHP serialization layer.
 */

import type { MediaItem } from './media/types';
import type { ContentWidthOption } from './theme/branding';

export interface Breadcrumb {
    label: string;
    url: string | null;
}

export interface FieldNode {
    component: string;
    name: string | null;
    value: unknown;
    old?: unknown;
    attributes: Record<string, unknown>;
    errors: string[];
    /** Present for Group field nodes. */
    fields?: FieldNode[];
    /** Present for custom ReactField nodes. */
    props?: Record<string, unknown>;
}

export interface ColumnNode {
    name: string;
    column: string;
    title: string;
    slug: string;
    align?: 'start' | 'center' | 'end';
    width?: string | null;
    sort?: boolean;
    sortUrl?: string;
    filter?: FieldNode | null;
    filterString?: string | null;
    filterTabs?: boolean;
    popover?: string | null;
    defaultHidden?: boolean;
    allowUserHidden?: boolean;
    /** Pre-rendered HTML for closure (render) columns. */
    rendered?: string | null;
}

export interface LayoutNode {
    type: string;
    key: string;
    canSee: boolean;
    data: Record<string, unknown>;
    children: LayoutNode[];
}

export interface ScreenProps {
    name: string | null;
    description: string | null;
    shell: {
        chrome?: 'default' | 'none' | null;
        contentWidth?: ContentWidthOption | null;
    };
    permission: string[] | null;
    breadcrumbs: Breadcrumb[];
    commandBar: FieldNode[];
    layout: LayoutNode[];
    data: Record<string, unknown>;
    state: string | null;
    screenComponent: string | null;
    formValidateMessage?: string;
    needPreventsAbandonment?: boolean;
}

/** Standard props passed to every field/action component. */
export interface FieldComponentProps {
    node: FieldNode;
    /** Repository data row/scope (model.* prefixed values live here). */
    data: Record<string, unknown>;
    value: unknown;
    name: string | null;
    attributes: Record<string, unknown>;
    errors: string[];
    onChange?: (value: unknown) => void;
    /** Optional hook used by AttachField when the selected media assets change. */
    onAssetsChange?: (assets: MediaItem[]) => void;
    screen?: ScreenContext;
}

/** Standard props passed to every layout component. */
export interface LayoutComponentProps {
    node: LayoutNode;
    data: Record<string, unknown>;
    screen?: ScreenContext;
}

/** Standard props passed to a custom full-screen component (escape hatch). */
export interface CustomComponentProps {
    data: Record<string, unknown>;
    value?: unknown;
    name?: string | null;
    attributes?: Record<string, unknown>;
    errors?: string[];
    onChange?: (value: unknown) => void;
    screen?: ScreenContext;
    /** Custom props provided server-side via ->props([...]) / Layout::component. */
    props?: Record<string, unknown>;
    /** For layout-level custom components, the layout subtree. */
    node?: LayoutNode;
}

export interface ScreenContext {
    name: string | null;
    description: string | null;
    breadcrumbs: Breadcrumb[];
    data: Record<string, unknown>;
    state: string | null;
}
