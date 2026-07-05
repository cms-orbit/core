import type {
    CustomComponentProps,
    FieldComponentProps,
    FieldNode,
    LayoutComponentProps,
    LayoutNode,
    ScreenContext,
} from './contract';
import { readLayoutData } from './lib/layout-data';
import { useOptionalOrbitForm } from './form-context';
import {
    MissingComponent,
    isBuiltInLayout,
    resolveAction,
    resolveComponent,
    resolveField,
    resolveLayout,
} from './registry';
import type { ActionComponent, CustomComponent, FieldComponent, LayoutComponent } from './registry';

type DataScope = Record<string, unknown>;

/** Render a single field node, binding value/errors/onChange to the form. */
export function FieldRenderer({
    node,
    data,
    screen,
}: {
    node: FieldNode;
    data: DataScope;
    screen?: ScreenContext;
}) {
    const form = useOptionalOrbitForm();

    if (!isFieldVisible(node, form)) {
        return null;
    }

    // Group nodes carry nested fields and have no value of their own.
    if (node.fields && node.fields.length > 0) {
        return (
            <div className="grid grid-cols-1 gap-x-4 md:grid-cols-2">
                {node.fields.map((child, index) => (
                    <FieldRenderer key={fieldKey(child, index)} node={child} data={data} screen={screen} />
                ))}
            </div>
        );
    }

    const FieldComponentImpl = resolveField(node.component) as FieldComponent | undefined;
    const ActionComponentImpl = FieldComponentImpl
        ? undefined
        : (resolveAction(node.component) as ActionComponent | undefined);
    const CustomComponentImpl =
        FieldComponentImpl || ActionComponentImpl
            ? undefined
            : (resolveComponent(node.component) as CustomComponent | undefined);

    if (!FieldComponentImpl && !ActionComponentImpl && !CustomComponentImpl) {
        return <MissingComponent kind="field" name={node.component} />;
    }

    const value = form ? form.getValue(node.name) ?? node.value : node.value;
    const error = form?.getError(node.name);
    const errors = error ? [error] : node.errors;
    const onChange = form ? (next: unknown) => form.setValue(node.name, next) : undefined;

    if (CustomComponentImpl) {
        return (
            <CustomComponentImpl
                data={data}
                value={value}
                name={node.name}
                attributes={node.attributes}
                errors={errors}
                onChange={onChange}
                screen={screen}
                props={node.props}
            />
        );
    }

    const componentProps: FieldComponentProps = {
        node,
        data,
        value,
        name: node.name,
        attributes: node.attributes,
        errors,
        onChange,
        screen,
    };

    const Component = (FieldComponentImpl ?? ActionComponentImpl) as FieldComponent;

    return <Component {...componentProps} />;
}

/** Render a single action node (command bar / inline action). */
export function ActionRenderer({
    node,
    data,
    screen,
}: {
    node: FieldNode;
    data: DataScope;
    screen?: ScreenContext;
}) {
    const Component = resolveAction(node.component) as FieldComponent | undefined;

    if (!Component) {
        return <MissingComponent kind="action" name={node.component} />;
    }

    const componentProps: FieldComponentProps = {
        node,
        data,
        value: node.value,
        name: node.name,
        attributes: node.attributes,
        errors: node.errors,
        screen,
    };

    return <Component {...componentProps} />;
}

/** Render a list of action nodes. */
export function ActionBar({
    actions,
    data,
    screen,
}: {
    actions: FieldNode[];
    data: DataScope;
    screen?: ScreenContext;
}) {
    if (!actions || actions.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {actions.map((action, index) => (
                <ActionRenderer key={fieldKey(action, index)} node={action} data={data} screen={screen} />
            ))}
        </div>
    );
}

/** Recursively render a layout node via the registry. */
export function LayoutNodeRenderer({
    node,
    data,
    screen,
}: {
    node: LayoutNode;
    data: DataScope;
    screen?: ScreenContext;
}) {
    if (node.canSee === false) {
        return null;
    }

    const customComponent = resolveComponent(node.type);

    if (customComponent && !isBuiltInLayout(node.type)) {
        const componentProps: CustomComponentProps = {
            data,
            screen,
            node,
            props: readLayoutData(node),
        };

        const CustomComponentImpl = customComponent;

        return <CustomComponentImpl {...componentProps} />;
    }

    const Component = resolveLayout(node.type) as LayoutComponent | undefined;

    if (!Component) {
        return <MissingComponent kind="layout" name={node.type} />;
    }

    const componentProps: LayoutComponentProps = { node, data, screen };

    return <Component {...componentProps} />;
}

/** Render the children of a layout node. */
export function LayoutChildren({
    nodes,
    data,
    screen,
    className,
}: {
    nodes: LayoutNode[];
    data: DataScope;
    screen?: ScreenContext;
    className?: string;
}) {
    if (!nodes || nodes.length === 0) {
        return null;
    }

    return (
        <div className={className}>
            {nodes.map((child) => (
                <LayoutNodeRenderer key={child.key} node={child} data={data} screen={screen} />
            ))}
        </div>
    );
}

/** Render an entire layout tree (the screen body). */
export function ScreenRenderer({
    layout,
    data,
    screen,
}: {
    layout: LayoutNode[];
    data: DataScope;
    screen?: ScreenContext;
}) {
    return (
        <div className="space-y-4">
            {layout.map((node) => (
                <LayoutNodeRenderer key={node.key} node={node} data={data} screen={screen} />
            ))}
        </div>
    );
}

function fieldKey(node: FieldNode, index: number): string {
    return `${node.name ?? node.component}-${index}`;
}

function isFieldVisible(
    node: FieldNode,
    form: ReturnType<typeof useOptionalOrbitForm>,
): boolean {
    const conditions = node.attributes?.visibleWhen;

    if (!conditions || typeof conditions !== 'object' || Array.isArray(conditions)) {
        return true;
    }

    const config = (form?.getValue('config') as Record<string, unknown> | undefined) ?? {};

    return Object.entries(conditions as Record<string, unknown>).every(([key, expected]) => {
        const actual = config[key];

        if (Array.isArray(expected)) {
            return expected.includes(actual);
        }

        return actual === expected;
    });
}
