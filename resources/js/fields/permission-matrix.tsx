import { useMemo, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { useOptionalOrbitForm } from '../form-context';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { FieldShell } from '../ui/field-shell';
import { Icon } from '../ui/icon';
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
type OverrideState = 'none' | 'inherit' | 'allow';

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

    return selectedRoleIds.reduce<PermissionMap>(
        (merged, roleId) => {
            const permissions = rolePermissionsById[roleId];

            if (!permissions) {
                return merged;
            }

            return { ...merged, ...permissions };
        },
        { ...fallback },
    );
}

function resolveOverrideState(explicit: boolean | undefined): OverrideState {
    if (explicit === true) {
        return 'allow';
    }

    if (explicit === false) {
        return 'none';
    }

    return 'inherit';
}

function isEffectivelyAllowed(explicit: boolean | undefined, inherited: boolean): boolean {
    if (explicit === true) {
        return true;
    }

    if (explicit === false) {
        return false;
    }

    return inherited;
}

function cycleOverrideState(current: OverrideState): OverrideState {
    if (current === 'none') {
        return 'inherit';
    }

    if (current === 'inherit') {
        return 'allow';
    }

    return 'none';
}

function groupAggregateState(states: OverrideState[]): OverrideState | 'mixed' {
    if (states.length === 0) {
        return 'inherit';
    }

    const unique = new Set(states);

    if (unique.size === 1) {
        return states[0];
    }

    return 'mixed';
}

/** Compact tri-state control: none [], inherit [-], allow [v]. */
function TriStateToggle({
    state,
    disabled,
    onChange,
    title,
    size = 'sm',
}: {
    state: OverrideState | 'mixed';
    disabled?: boolean;
    onChange?: (next: OverrideState) => void;
    title?: string;
    size?: 'sm' | 'md';
}) {
    const t = useT();
    const boxClass = size === 'md' ? 'h-5 w-5' : 'h-4 w-4';
    const iconClass = size === 'md' ? 'text-xs' : 'text-[10px]';

    const label =
        title ??
        (state === 'allow'
            ? t('Allow')
            : state === 'inherit'
              ? t('Inherited')
              : state === 'mixed'
                ? t('Mixed')
                : t('No permission'));

    return (
        <button
            type="button"
            disabled={disabled}
            title={label}
            aria-label={label}
            onClick={(event) => {
                event.stopPropagation();

                if (disabled || !onChange) {
                    return;
                }

                onChange(cycleOverrideState(state === 'mixed' ? 'none' : state));
            }}
            className={cn(
                'inline-flex shrink-0 items-center justify-center rounded border transition',
                boxClass,
                disabled && 'cursor-not-allowed opacity-60',
                state === 'allow' &&
                    'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-500/10 dark:text-emerald-300',
                state === 'inherit' &&
                    'border-gray-400 bg-gray-50 text-gray-600 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300',
                (state === 'none' || state === 'mixed') &&
                    'border-gray-300 bg-white text-gray-300 hover:border-gray-400 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-600',
            )}
        >
            {state === 'allow' ? (
                <Icon name="bs.check-lg" className={iconClass} />
            ) : state === 'inherit' ? (
                <Icon name="bs.dash-lg" className={iconClass} />
            ) : null}
        </button>
    );
}

export function PermissionMatrixField(props: FieldComponentProps) {
    const t = useT();
    const form = useOptionalOrbitForm();
    const { value, errors, onChange, name } = props;
    const groups = (props.node.props?.groups as PermissionGroup[] | undefined) ?? [];
    const inheritedFromServer = (props.node.props?.inheritedPermissions as PermissionMap | undefined) ?? {};
    const rolePermissionsById = attr<Record<string, PermissionMap>>(props, 'rolePermissionsById') ?? {};
    const disabled = bool(attr(props, 'disabled')) || bool(attr(props, 'readOnly'));
    const readOnly = bool(attr(props, 'readOnly'));
    const collapsible = bool(attr(props, 'collapsible'));
    const defaultCollapsed = bool(attr(props, 'defaultCollapsed'));
    const sectionsDefaultCollapsed = attr(props, 'sectionsDefaultCollapsed') !== false;
    const rolesFieldName = siblingFieldName(name, 'roles');
    const selectedRoleIdsFromProps = toStringArray(props.node.props?.selectedRoleIds);
    const selectedRoleIds = readOnly
        ? selectedRoleIdsFromProps
        : toStringArray(rolesFieldName ? form?.getValue(rolesFieldName) : undefined);

    const inheritedPermissions = useMemo(
        () => mergeRolePermissions(selectedRoleIds, rolePermissionsById, inheritedFromServer),
        [selectedRoleIds, rolePermissionsById, inheritedFromServer],
    );
    const explicitPermissions = useMemo(() => toPermissionMap(value), [value]);
    const overrideMode =
        readOnly ||
        Object.keys(rolePermissionsById).length > 0 ||
        Object.keys(inheritedPermissions).length > 0;

    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(() =>
        Object.fromEntries(groups.map((group) => [group.group, !sectionsDefaultCollapsed])),
    );

    const emit = (next: PermissionMap) => {
        if (readOnly) {
            return;
        }

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
        } else if (state === 'allow') {
            next[slug] = true;
        } else {
            next[slug] = false;
        }

        emit(next);
    };

    const setGroupOverride = (group: PermissionGroup, state: OverrideState) => {
        const next = { ...explicitPermissions };

        for (const permission of group.permissions) {
            if (state === 'inherit') {
                delete next[permission.slug];
            } else if (state === 'allow') {
                next[permission.slug] = true;
            } else {
                next[permission.slug] = false;
            }
        }

        emit(next);
    };

    const toggleSimple = (slug: string, checked: boolean) => {
        emit({
            ...explicitPermissions,
            [slug]: checked,
        });
    };

    const toggleGroupSimple = (group: PermissionGroup, checked: boolean) => {
        const next = { ...explicitPermissions };

        for (const permission of group.permissions) {
            next[permission.slug] = checked;
        }

        emit(next);
    };

    const toggleGroupOpen = (groupKey: string) => {
        setOpenGroups((current) => ({
            ...current,
            [groupKey]: !current[groupKey],
        }));
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
                <div className="space-y-2">
                    {groups.map((group) => {
                        const total = group.permissions.length;
                        const states = group.permissions.map((permission) =>
                            resolveOverrideState(explicitPermissions[permission.slug]),
                        );
                        const allowedCount = group.permissions.filter((permission) =>
                            isEffectivelyAllowed(
                                explicitPermissions[permission.slug],
                                inheritedPermissions[permission.slug] ?? false,
                            ),
                        ).length;
                        const aggregate = groupAggregateState(states);
                        const isOpen = openGroups[group.group] ?? false;
                        const allSimpleChecked =
                            total > 0 &&
                            group.permissions.every((permission) => explicitPermissions[permission.slug] ?? false);
                        const someSimpleChecked =
                            group.permissions.some((permission) => explicitPermissions[permission.slug] ?? false) &&
                            !allSimpleChecked;

                        return (
                            <div
                                key={group.group}
                                className="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                            >
                                <div className="flex items-center gap-2 bg-gray-50 px-3 py-2 dark:bg-gray-800/50">
                                    <button
                                        type="button"
                                        onClick={() => toggleGroupOpen(group.group)}
                                        className="flex min-w-0 flex-1 items-center gap-2 text-left text-sm font-medium text-gray-700 dark:text-gray-200"
                                    >
                                        <Icon
                                            name="bs.chevron-down"
                                            className={cn(
                                                'shrink-0 text-xs text-gray-400 transition-transform',
                                                !isOpen && '-rotate-90',
                                            )}
                                        />
                                        <span className="truncate">{group.group}</span>
                                        <span className="ml-1 text-xs font-normal text-gray-400">
                                            {allowedCount}/{total}
                                        </span>
                                    </button>

                                    {overrideMode ? (
                                        <TriStateToggle
                                            state={aggregate}
                                            disabled={disabled}
                                            onChange={(next) => setGroupOverride(group, next)}
                                            title={t('Toggle all permissions in this group')}
                                        />
                                    ) : (
                                        <input
                                            type="checkbox"
                                            className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                                            checked={allSimpleChecked}
                                            ref={(element) => {
                                                if (element) {
                                                    element.indeterminate = someSimpleChecked;
                                                }
                                            }}
                                            disabled={disabled}
                                            onChange={(event) => toggleGroupSimple(group, event.target.checked)}
                                            onClick={(event) => event.stopPropagation()}
                                        />
                                    )}
                                </div>

                                {isOpen ? (
                                    <div className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {overrideMode ? (
                                            <div className="flex items-center gap-2 px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                                                <TriStateToggle
                                                    state={aggregate}
                                                    disabled={disabled}
                                                    onChange={(next) => setGroupOverride(group, next)}
                                                    size="md"
                                                />
                                                <span className="font-medium text-gray-700 dark:text-gray-200">
                                                    {t('All permissions')}
                                                </span>
                                            </div>
                                        ) : null}

                                        {group.permissions.map((permission) => {
                                            const explicit = explicitPermissions[permission.slug];
                                            const inherited = inheritedPermissions[permission.slug] ?? false;
                                            const state = resolveOverrideState(explicit);
                                            const allowed = isEffectivelyAllowed(explicit, inherited);

                                            return overrideMode ? (
                                                <div
                                                    key={permission.slug}
                                                    className="flex items-center gap-2 px-3 py-1.5"
                                                >
                                                    <TriStateToggle
                                                        state={state}
                                                        disabled={disabled}
                                                        onChange={(next) => setOverride(permission.slug, next)}
                                                    />
                                                    <span
                                                        className={cn(
                                                            'min-w-0 flex-1 truncate text-sm',
                                                            state === 'none' && 'text-gray-400 dark:text-gray-500',
                                                            state === 'inherit' &&
                                                                (allowed
                                                                    ? 'text-gray-700 dark:text-gray-200'
                                                                    : 'text-gray-400 dark:text-gray-500'),
                                                            state === 'allow' && 'text-gray-900 dark:text-gray-100',
                                                        )}
                                                    >
                                                        {permission.label}
                                                    </span>
                                                    {state === 'inherit' && allowed ? (
                                                        <span className="shrink-0 text-[10px] uppercase tracking-wide text-gray-400">
                                                            {t('Inherited')}
                                                        </span>
                                                    ) : null}
                                                </div>
                                            ) : (
                                                <label
                                                    key={permission.slug}
                                                    className="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                                                        checked={explicitPermissions[permission.slug] ?? false}
                                                        disabled={disabled}
                                                        onChange={(event) =>
                                                            toggleSimple(permission.slug, event.target.checked)
                                                        }
                                                    />
                                                    <span className="truncate">{permission.label}</span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                ) : null}
                            </div>
                        );
                    })}
                </div>
            )}
        </FieldShell>
    );
}
