import type { CustomComponentProps } from '../contract';
import type { FieldComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { UiButton } from '../ui/button';
import { FieldShell, inputClass } from '../ui/field-shell';
import { PermissionMatrixField } from './permission-matrix';
import { useMemo, type ReactNode } from 'react';

type EmailEditorRow = {
    address: string;
    is_primary: boolean;
};

function readEmailRows(value: unknown): EmailEditorRow[] {
    if (!Array.isArray(value) || value.length === 0) {
        return [{ address: '', is_primary: true }];
    }

    return value.map((row) => {
        const record = row && typeof row === 'object' ? (row as Record<string, unknown>) : {};

        return {
            address: typeof record.address === 'string' ? record.address : '',
            is_primary: Boolean(record.is_primary),
        };
    });
}

/** XE3-style multi-email editor with primary selection. */
export function UserEmailsEditor({
    value,
    onChange,
    errors,
    attributes,
    props: customProps,
}: CustomComponentProps) {
    const t = useT();
    const rows = useMemo(() => readEmailRows(value), [value]);
    const addLabel = (customProps?.addLabel as string | undefined) ?? t('Add email');
    const primaryLabel = (customProps?.primaryLabel as string | undefined) ?? t('Primary');
    const addressLabel = (customProps?.addressLabel as string | undefined) ?? t('Email address');

    const commit = (next: EmailEditorRow[]) => onChange?.(next);

    const updateAddress = (index: number, address: string) => {
        commit(rows.map((row, rowIndex) => (rowIndex === index ? { ...row, address } : row)));
    };

    const setPrimary = (index: number) => {
        commit(
            rows.map((row, rowIndex) => ({
                ...row,
                is_primary: rowIndex === index,
            })),
        );
    };

    const addRow = () => {
        commit([...rows, { address: '', is_primary: rows.length === 0 }]);
    };

    const removeRow = (index: number) => {
        const next = rows.filter((_, rowIndex) => rowIndex !== index);

        if (next.length === 0) {
            commit([{ address: '', is_primary: true }]);

            return;
        }

        if (!next.some((row) => row.is_primary)) {
            next[0] = { ...next[0], is_primary: true };
        }

        commit(next);
    };

    return (
        <FieldShell
            title={(attributes?.title as string | undefined) ?? t('Email addresses')}
            help={(attributes?.help as string | undefined) ?? undefined}
            error={errors?.[0]}
        >
            <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th className="w-24 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {primaryLabel}
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {addressLabel}
                            </th>
                            <th className="w-10" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {rows.map((row, index) => (
                            <tr key={index}>
                                <td className="px-3 py-2">
                                    <label className="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <input
                                            type="radio"
                                            name="user-emails-primary"
                                            checked={row.is_primary}
                                            onChange={() => setPrimary(index)}
                                            className="text-orbit-primary focus:ring-orbit-primary"
                                        />
                                        {row.is_primary ? (
                                            <span className="text-xs font-medium text-orbit-primary">{primaryLabel}</span>
                                        ) : null}
                                    </label>
                                </td>
                                <td className="px-2 py-1">
                                    <input
                                        type="email"
                                        className={inputClass}
                                        value={row.address}
                                        placeholder={t('name@example.com')}
                                        onChange={(event) => updateAddress(index, event.target.value)}
                                    />
                                </td>
                                <td className="px-2 py-1 text-center">
                                    <button
                                        type="button"
                                        onClick={() => removeRow(index)}
                                        className="text-gray-400 hover:text-red-600"
                                        aria-label={t('Remove email')}
                                    >
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="mt-2">
                <UiButton type="button" variant="default" onClick={addRow}>
                    {addLabel}
                </UiButton>
            </div>
        </FieldShell>
    );
}

type DetailRow = {
    label: string;
    value?: string | null;
    html?: string | null;
};

type EmailRow = {
    address: string;
    is_primary: boolean;
    verified: boolean;
};

type LinkedAccount = {
    id: number;
    provider: string;
    provider_label: string;
    identifier: string;
    is_primary: boolean;
    verified: boolean;
    last_used_at?: string | null;
    view_url?: string | null;
};

function providerBadgeClass(provider: string): string {
    switch (provider) {
        case 'email':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300';
        case 'id':
            return 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300';
        case 'phone':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
        case 'google':
            return 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300';
        case 'kakao':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200';
        case 'apple':
            return 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300';
    }
}

function DetailSection({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <h3 className="border-b border-gray-100 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:text-gray-400">
                {title}
            </h3>
            {children}
        </section>
    );
}

function DetailRows({ rows }: { rows: DetailRow[] }) {
    return (
        <dl className="space-y-2">
            {rows.map((row) => (
                <div key={row.label} className="grid grid-cols-[minmax(0,8rem)_1fr] gap-3 text-sm">
                    <dt className="text-gray-500 dark:text-gray-400">{row.label}</dt>
                    <dd className="min-w-0 text-gray-900 dark:text-gray-100">
                        {row.html ? (
                            <span dangerouslySetInnerHTML={{ __html: row.html }} />
                        ) : (
                            row.value || '—'
                        )}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

/** Read-only user detail panel with the same two-column structure as the edit form. */
export function UserDetailView({ props: customProps }: CustomComponentProps) {
    const t = useT();
    const name = String(customProps?.name ?? '—');
    const avatarUrl = customProps?.avatar_url as string | null | undefined;
    const profileRows = (customProps?.profile_rows as DetailRow[] | undefined) ?? [];
    const loginRows = (customProps?.login_rows as DetailRow[] | undefined) ?? [];
    const emails = (customProps?.emails as EmailRow[] | undefined) ?? [];
    const roles = (customProps?.roles as string[] | undefined) ?? [];
    const permissionGroups = customProps?.permission_groups;
    const explicitPermissions = customProps?.explicit_permissions;
    const inheritedPermissions = customProps?.inherited_permissions;
    const selectedRoleIds = customProps?.selected_role_ids;

    const permissionField: FieldComponentProps = {
        node: {
            component: 'permission-matrix',
            name: null,
            value: explicitPermissions ?? {},
            attributes: {
                readOnly: true,
                sectionsDefaultCollapsed: true,
                title: t('Direct permissions'),
            },
            props: {
                groups: permissionGroups,
                inheritedPermissions: inheritedPermissions ?? {},
                selectedRoleIds: selectedRoleIds ?? [],
            },
            errors: [],
        },
        data: {},
        value: explicitPermissions ?? {},
        name: null,
        attributes: {
            readOnly: true,
            sectionsDefaultCollapsed: true,
            title: t('Direct permissions'),
        },
        errors: [],
    };

    return (
        <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <div className="space-y-6">
                <DetailSection title={t('Profile')}>
                    <div className="flex items-start gap-4">
                        {avatarUrl ? (
                            <img
                                src={avatarUrl}
                                alt=""
                                className="h-16 w-16 shrink-0 rounded-full border border-gray-200 object-cover dark:border-gray-700"
                            />
                        ) : (
                            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gray-100 text-lg font-semibold text-gray-500 dark:bg-gray-800">
                                {name.slice(0, 1).toUpperCase()}
                            </div>
                        )}
                        <div className="min-w-0 flex-1">
                            <p className="text-lg font-semibold text-gray-900 dark:text-gray-100">{name}</p>
                            <DetailRows rows={profileRows} />
                        </div>
                    </div>
                </DetailSection>

                <DetailSection title={t('Login & security')}>
                    {emails.length > 0 ? (
                        <div className="mb-3 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {t('Email address')}
                                        </th>
                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            {t('Status')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                    {emails.map((email) => (
                                        <tr key={email.address}>
                                            <td className="px-3 py-2 font-mono text-xs text-gray-700 dark:text-gray-200">
                                                {email.address}
                                                {email.is_primary ? (
                                                    <span className="ml-2 text-[10px] font-medium uppercase tracking-wide text-orbit-primary">
                                                        {t('Primary')}
                                                    </span>
                                                ) : null}
                                            </td>
                                            <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                                                {email.verified ? t('Verified') : t('Unverified')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : null}
                    <DetailRows rows={loginRows} />
                </DetailSection>
            </div>

            <div className="space-y-6">
                <DetailSection title={t('Roles')}>
                    {roles.length === 0 ? (
                        <p className="text-sm text-gray-400">{t('No roles')}</p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {roles.map((role) => (
                                <span
                                    key={role}
                                    className="inline-flex items-center rounded-full bg-orbit-primary/10 px-2.5 py-0.5 text-xs font-medium text-orbit-primary"
                                >
                                    {role}
                                </span>
                            ))}
                        </div>
                    )}
                </DetailSection>

                <PermissionMatrixField {...permissionField} />
            </div>
        </div>
    );
}

export function UserLinkedAccountsPanel({ props: customProps }: CustomComponentProps) {
    const t = useT();
    const accounts = (customProps?.accounts as LinkedAccount[] | undefined) ?? [];
    const manageUrl = customProps?.manage_url as string | undefined;
    const emptyLabel = (customProps?.emptyLabel as string | undefined) ?? t('No linked accounts yet.');

    if (accounts.length === 0) {
        return (
            <div className="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400 dark:border-gray-700">
                {emptyLabel}
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {manageUrl ? (
                <div className="flex justify-end">
                    <a
                        href={manageUrl}
                        className="text-xs font-medium text-orbit-primary hover:underline"
                    >
                        {t('Manage all accounts')}
                    </a>
                </div>
            ) : null}

            <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {t('Provider')}
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {t('Identifier')}
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {t('Status')}
                            </th>
                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {t('Last used')}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {accounts.map((account) => (
                            <tr key={account.id}>
                                <td className="px-3 py-2">
                                    <span
                                        className={cn(
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            providerBadgeClass(account.provider),
                                        )}
                                    >
                                        {account.provider_label}
                                    </span>
                                    {account.is_primary ? (
                                        <span className="ml-2 text-[10px] font-medium uppercase tracking-wide text-orbit-primary">
                                            {t('Primary')}
                                        </span>
                                    ) : null}
                                </td>
                                <td className="px-3 py-2 font-mono text-xs text-gray-700 dark:text-gray-200">
                                    {account.view_url ? (
                                        <a href={account.view_url} className="hover:text-orbit-primary hover:underline">
                                            {account.identifier}
                                        </a>
                                    ) : (
                                        account.identifier
                                    )}
                                </td>
                                <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                                    {account.verified ? t('Verified') : t('Unverified')}
                                </td>
                                <td className="px-3 py-2 text-xs text-gray-500">
                                    {account.last_used_at ?? '—'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
