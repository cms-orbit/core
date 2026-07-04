import * as BootstrapIcons from 'react-bootstrap-icons';
import type { Icon as BootstrapIconComponent } from 'react-bootstrap-icons';

/**
 * Server-driven icons arrive as dotted names (e.g. "bs.plus-circle"), faithful
 * to the PHP contract that the previous Blade icon set used. We intentionally
 * include the full Bootstrap icon export set at build time so new server-side
 * `bs.*` names do not require manual React registry updates. Unknown names still
 * render nothing.
 */
const registry = BootstrapIcons as Record<string, BootstrapIconComponent>;

const resolveCache = new Map<string, BootstrapIconComponent | null>();

function toPascalCase(value: string): string {
    return value
        .split(/[-_\s]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
}

function toBootstrapComponentName(value: string): string {
    const componentName = toPascalCase(value);

    return /^\d/.test(componentName) ? `Icon${componentName}` : componentName;
}

function resolveIcon(name: string): BootstrapIconComponent | null {
    const cached = resolveCache.get(name);

    if (cached !== undefined) {
        return cached;
    }

    // Drop an optional set prefix ("bs.gear" -> "gear").
    const bare = name.includes('.') ? name.slice(name.indexOf('.') + 1) : name;
    const component = registry[toBootstrapComponentName(bare)] ?? null;

    resolveCache.set(name, component);

    return component;
}

export interface IconProps {
    name?: string | null;
    size?: number | string;
    className?: string;
}

export function Icon({ name, size = '1em', className }: IconProps) {
    if (!name) {
        return null;
    }

    const Resolved = resolveIcon(name);

    if (!Resolved) {
        if (import.meta.env?.DEV) {
            console.warn(`[orbit] No icon registered for "${name}".`);
        }

        return null;
    }

    return <Resolved size={size} className={className} aria-hidden focusable={false} />;
}
