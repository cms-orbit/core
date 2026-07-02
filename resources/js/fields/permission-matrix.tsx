import { useEffect, useMemo, useRef } from 'react';
import type { FieldComponentProps } from '../contract';
import { useT } from '../lib/i18n';
import { FieldShell } from '../ui/field-shell';
import { attr } from './shared';

interface PermissionEntry {
    slug: string;
    label: string;
}

interface PermissionGroup {
    group: string;
    permissions: PermissionEntry[];
}

/** Normalise the incoming value (a `{slug:true}` map or a slug array) to a set. */
function toSelectedSet(value: unknown): Set<string> {
    if (Array.isArray(value)) {
        return new Set(value.map((slug) => String(slug)));
    }

    if (value && typeof value === 'object') {
        return new Set(
            Object.entries(value as Record<string, unknown>)
                .filter(([, enabled]) => Boolean(enabled))
                .map(([slug]) => slug),
        );
    }

    return new Set();
}

/** Header checkbox that reflects a group's all / none / partial selection. */
function GroupToggle({
    checked,
    indeterminate,
    onChange,
}: {
    checked: boolean;
    indeterminate: boolean;
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
    const { value, errors, onChange } = props;
    const groups = (props.node.props?.groups as PermissionGroup[] | undefined) ?? [];

    const selected = useMemo(() => toSelectedSet(value), [value]);

    const emit = (next: Set<string>) => {
        onChange?.(Array.from(next));
    };

    const toggle = (slug: string, checked: boolean) => {
        const next = new Set(selected);
        if (checked) {
            next.add(slug);
        } else {
            next.delete(slug);
        }
        emit(next);
    };

    const toggleGroup = (group: PermissionGroup, checked: boolean) => {
        const next = new Set(selected);
        for (const permission of group.permissions) {
            if (checked) {
                next.add(permission.slug);
            } else {
                next.delete(permission.slug);
            }
        }
        emit(next);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            {groups.length === 0 ? (
                <p className="text-sm text-gray-400">{t('No permissions')}</p>
            ) : (
                <div className="space-y-3">
                    {groups.map((group) => {
                        const total = group.permissions.length;
                        const checkedCount = group.permissions.filter((permission) =>
                            selected.has(permission.slug),
                        ).length;
                        const allChecked = total > 0 && checkedCount === total;
                        const someChecked = checkedCount > 0 && checkedCount < total;

                        return (
                            <div
                                key={group.group}
                                className="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                            >
                                <label className="flex cursor-pointer items-center gap-2 border-b border-gray-100 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-200">
                                    <GroupToggle
                                        checked={allChecked}
                                        indeterminate={someChecked}
                                        onChange={(checked) => toggleGroup(group, checked)}
                                    />
                                    <span>{group.group}</span>
                                    <span className="ml-auto text-xs font-normal text-gray-400">
                                        {checkedCount}/{total}
                                    </span>
                                </label>
                                <div className="grid grid-cols-1 gap-1 px-3 py-2 sm:grid-cols-2 lg:grid-cols-3">
                                    {group.permissions.map((permission) => (
                                        <label
                                            key={permission.slug}
                                            className="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"
                                        >
                                            <input
                                                type="checkbox"
                                                className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                                                checked={selected.has(permission.slug)}
                                                onChange={(event) =>
                                                    toggle(permission.slug, event.target.checked)
                                                }
                                            />
                                            <span className="truncate">{permission.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </FieldShell>
    );
}
