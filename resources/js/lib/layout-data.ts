import type { LayoutNode } from '../contract';

/** Read props passed to a custom component layout (`Layout::component`). */
export function readLayoutData(node: LayoutNode): Record<string, unknown> {
    const payload = node.data;
    const nested = payload.props;

    if (nested && typeof nested === 'object' && !Array.isArray(nested)) {
        return nested as Record<string, unknown>;
    }

    return payload;
}
