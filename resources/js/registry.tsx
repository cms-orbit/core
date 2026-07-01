import type { ComponentType } from 'react';
import type {
    CustomComponentProps,
    FieldComponentProps,
    LayoutComponentProps,
} from './contract';

export type FieldComponent = ComponentType<FieldComponentProps>;
export type LayoutComponent = ComponentType<LayoutComponentProps>;
export type ActionComponent = ComponentType<FieldComponentProps>;
export type CustomComponent = ComponentType<CustomComponentProps>;
export type AnyComponent = ComponentType<Record<string, unknown>>;

const layoutRegistry: Record<string, LayoutComponent> = {};
const fieldRegistry: Record<string, FieldComponent> = {};
const actionRegistry: Record<string, ActionComponent> = {};
/** Custom components are visible to every renderer (layout/field/action/screen). */
const customRegistry: Record<string, CustomComponent> = {};

export function registerLayout(type: string, component: LayoutComponent): void {
    layoutRegistry[type] = component;
}

export function registerLayouts(map: Record<string, LayoutComponent>): void {
    Object.assign(layoutRegistry, map);
}

export function registerField(name: string, component: FieldComponent): void {
    fieldRegistry[name] = component;
}

export function registerFields(map: Record<string, FieldComponent>): void {
    Object.assign(fieldRegistry, map);
}

export function registerAction(name: string, component: ActionComponent): void {
    actionRegistry[name] = component;
}

export function registerActions(map: Record<string, ActionComponent>): void {
    Object.assign(actionRegistry, map);
}

/**
 * The primary escape-hatch API. Host apps and packages inject their custom
 * React components, made available to the screen renderer by name.
 */
export function registerComponents(map: Record<string, CustomComponent>): void {
    Object.assign(customRegistry, map);
}

export function resolveLayout(type: string): LayoutComponent | CustomComponent | undefined {
    return layoutRegistry[type] ?? customRegistry[type];
}

export function resolveField(name: string): FieldComponent | CustomComponent | undefined {
    return fieldRegistry[name] ?? customRegistry[name];
}

export function resolveAction(name: string): ActionComponent | CustomComponent | undefined {
    return actionRegistry[name] ?? customRegistry[name];
}

export function resolveComponent(name: string): CustomComponent | undefined {
    return customRegistry[name];
}

/** A clearly visible placeholder rendered for unregistered component names. */
export function MissingComponent({ kind, name }: { kind: string; name: string }) {
    if (import.meta.env?.DEV) {
         
        console.warn(`[orbit] No ${kind} component registered for "${name}".`);
    }

    return (
        <div className="rounded-md border border-dashed border-amber-400 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-500/50 dark:bg-amber-500/10 dark:text-amber-300">
            Missing {kind}: <code className="font-mono">{name}</code>
        </div>
    );
}
