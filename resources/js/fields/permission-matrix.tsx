import { useEffect, useMemo, useRef } from 'react';
import type { FieldComponentProps } from '../contract';
import { useOptionalOrbitForm } from '../form-context';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { FieldShell } from '../ui/field-shell';
import { attr, bool } from './shared';

interface PermissionEntry {
    slug: string;
    label: string;
}

interface PermissionGroup {
    group: string;
    permissions: PermissionEntry[];
}

type PermissionMap = Record<string, boolean>;
type OverrideState = 'inherit' | 'allow' | 'deny';

/** Normalize a `{slug:true|false}` map or a slug array into a boolean map. */
function toPermissionMap(value: unknown): PermissionMap {
    if (Array.isArray(value)) {
        return Object.fromEntries(value.map((slug) => [String(slug), true]));
    }

    if (value && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value as Record<string, unknown>)
                .filter(([, enabled]) => typeof enabled === 'boolean')
                .map(([slug, enabled]) => [slug, enabled as boolean]),
        );
    }

    return {};
}

function toStringArray(value: unknown): string[] {
    if (Array.isArray(value)) {
        return value.map((item) => String(item));
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    return [String(value)];
}

function siblingFieldName(name: string | null, field: string): string | null {
    if (!name) {
        return null;
    }

    if (name.includes('[')) {
        return name.replace(/\[[^\]]+\]$/, `[${field}]`);
    }

    return name.replace(/[^.]+$/, field);
}

function mergeRolePermissions(
    selectedRoleIds: string[],
    rolePermissionsById: Record<string, PermissionMap>,
    fallback: PermissionMap,
): PermissionMap {
    if (selectedRoleIds.length === 0) {
        return fallback;
    }

    return selectedRoleIds.reduce<PermissionMap>((merged, roleId) => {
        const permissions = rolePermissionsById[roleId];

        if (!permissions) {
            return merged;
        }

        return { ...merged, ...permissions };
    }, {});
}

/** Header checkbox that reflects a group's all / none / partial selection. */
function GroupToggle({
    checked,
    indeterminate,
    disabled,
    onChange,
}: {
    checked: boolean;
    indeterminate: boolean;
    disabled: boolean;
    onChange: (checked: boolean) => void;
}) {
    const ref = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (ref.current) {
            ref.current.indeterminate = indeterminate;
        }
    }, [indeterminate]);

    return (
        <input
            ref={ref}
            type="checkbox"
            className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
            checked={checked}
            disabled={disabled}
            onChange={(event) => onChange(event.target.checked)}
        />
    );
}

/**
 * Grouped permission picker. Renders one card per permission group with a
 * "select all" toggle in the header and a checkbox per permission. Emits the
 * checked slugs as an array (see the PHP PermissionMatrix field).
 */
export function PermissionMatrixField(props: FieldComponentProps) {
    const t = useT();
    const form = useOptionalOrbitForm();
    const { value, errors, onChange, name } = props;
    const groups = (props.node.props?.groups as PermissionGroup[] | undefined) ?? [];
    const inheritedFromServer = ((props.node.props?.inheritedPermissions as PermissionMap | undefined) ?? {});
    const rolePermissionsById =
        (attr<Record<string, PermissionMap>>(props, 'rolePermissionsById') ?? {});
    const disabled = bool(attr(props, 'disabled'));
    const collapsible = bool(attr(props, 'collapsible'));
    const defaultCollapsed = bool(attr(props, 'defaultCollapsed'));
    const rolesFieldName = siblingFieldName(name, 'roles');
    const selectedRoleIds = toStringArray(rolesFieldName ? form?.getValue(rolesFieldName) : undefined);

    const inheritedPermissions = useMemo(
        () => mergeRolePermissions(selectedRoleIds, rolePermissionsById, inheritedFromServer),
        [selectedRoleIds, rolePermissionsById, inheritedFromServer],
    );
    const explicitPermissions = useMemo(() => toPermissionMap(value), [value]);
    const overrideMode =
        Object.keys(rolePermissionsById).length > 0 || Object.keys(inheritedPermissions).length > 0;

    const emit = (next: PermissionMap) => {
        if (overrideMode) {
            onChange?.(next);

            return;
        }

        onChange?.(
            Object.entries(next)
                .filter(([, enabled]) => enabled)
                .map(([slug]) => slug),
        );
    };

    const setOverride = (slug: string, state: OverrideState) => {
        const next = { ...explicitPermissions };

        if (state === 'inherit') {
            delete next[slug];
        } else {
            next[slug] = state === 'allow';
        }

        emit(next);
    };

    const toggle = (slug: string, checked: boolean) => {
        if (overrideMode) {
            setOverride(slug, checked ? 'allow' : 'deny');

            return;
        }

        emit({
            ...explicitPermissions,
            [slug]: checked,
        });
    };

    const toggleGroup = (group: PermissionGroup, checked: boolean) => {
        const next = { ...explicitPermissions };

        for (const permission of group.permissions) {
            next[permission.slug] = checked;
        }

        emit(next);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
            collapsible={collapsible}
            defaultCollapsed={defaultCollapsed}
        >
            {groups.length === 0 ? (
                <p className="text-sm text-gray-400">{t('No permissions')}</p>
            ) : (
                <div className="space-y-3">
                    {groups.map((group) => {
                        const total = group.permissions.length;
                        const checkedCount = group.permissions.filter((permission) =>
                            explicitPermissions[permission.slug] ?? inheritedPermissions[permission.slug] ?? false,
                        ).length;
                        const allChecked = total > 0 && checkedCount === total;
                        const someChecked = checkedCount > 0 && checkedCount < total;

                        return (
                            <div
                                key={group.group}
                                className="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                            >
                                <label className="flex cursor-pointer items-center gap-2 border-b border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-200">
                                    {!overrideMode ? (
                                        <GroupToggle
                                            checked={allChecked}
                                            indeterminate={someChecked}
                                            disabled={disabled}
                                            onChange={(checked) => toggleGroup(group, checked)}
                                        />
                                    ) : (
                                        <span className="w-4" />
                                    )}
                                    <span>{group.group}</span>
                                    <span className="ml-auto text-xs font-normal text-gray-400">
                                        {checkedCount}/{total}
                                    </span>
                                </label>
                                <div className="grid grid-cols-1 gap-2 px-3 py-2 sm:grid-cols-2">
                                    {group.permissions.map((permission) => {
                                        const explicit = explicitPermissions[permission.slug];
                                        const inherited = inheritedPermissions[permission.slug] ?? false;
                                        const effective = explicit ?? inherited;
                                        const isInherited = explicit === undefined;

                                        return overrideMode ? (
                                            <div
                                                key={permission.slug}
                                                className="rounded-md border border-gray-100 px-3 py-2 dark:border-gray-700"
                                            >
                                                <div className="flex items-start gap-2">
                                                    <input
                                                        ref={(element) => {
                                                            if (element) {
                                                                element.indeterminate = isInherited && inherited;
                                                            }
                                                        }}
                                                        type="checkbox"
                                                        className="mt-0.5 h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                                                        checked={effective}
                                                        disabled={disabled}
                                                        onChange={(event) =>
                                                            toggle(permission.slug, event.target.checked)
                                                        }
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <div className="truncate text-sm text-gray-700 dark:text-gray-200">
                                                            {permission.label}
                                                        </div>
                                                        <div className="mt-2 inline-flex flex-wrap gap-1">
                                                            {([
                                                                ['inherit', t('Inherited')],
                                                                ['allow', t('Allow')],
                                                                ['deny', t('Deny')],
                                                            ] as const).map(([state, label]) => {
                                                                const active =
                                                                    (state === 'inherit' && isInherited) ||
                                                                    (state === 'allow' && explicit === true) ||
                                                                    (state === 'deny' && explicit === false);

                                                                return (
                                                                    <button
                                                                        key={state}
                                                                        type="button"
                                                                        disabled={disabled}
                                                                        onClick={() => setOverride(permission.slug, state)}
                                                                        className={cn(
                                                                            'rounded-md border px-2 py-1 text-xs transition',
                                                                            active
                                                                                ? 'border-orbit-primary bg-orbit-primary/10 font-medium text-orbit-primary'
                                                                                : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800',
                                                                        )}
                                                                    >
                                                                        {label}
                                                                    </button>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <label
                                                key={permission.slug}
                                                className="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                                                    checked={explicitPermissions[permission.slug] ?? false}
                                                    disabled={disabled}
                                                    onChange={(event) =>
                                                        toggle(permission.slug, event.target.checked)
                                                    }
                                                />
                                                <span className="truncate">{permission.label}</span>
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </FieldShell>
    );
}
