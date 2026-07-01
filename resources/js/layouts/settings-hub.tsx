import { Link } from '@inertiajs/react';
import type { LayoutComponentProps } from '../contract';
import { Icon } from '../ui/icon';

interface SettingsGroup {
    title: string;
    description?: string | null;
    icon?: string | null;
    uriKey: string;
    url: string;
}

function asGroups(value: unknown): SettingsGroup[] {
    return Array.isArray(value) ? (value as SettingsGroup[]) : [];
}

/**
 * Settings hub (`settings-hub` component layout). Renders a responsive card grid
 * of every config group the current user may access, fed the `groups` prop from
 * the SettingsScreen query.
 */
export function SettingsHubLayout({ data }: LayoutComponentProps) {
    const groups = asGroups(data.groups);

    if (groups.length === 0) {
        return (
            <p className="py-12 text-center text-sm text-gray-400">
                No settings groups available.
            </p>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {groups.map((group) => (
                <Link
                    key={group.uriKey}
                    href={group.url}
                    className="group flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-5 transition hover:border-orbit-primary/40 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-orbit-primary/10 text-orbit-primary">
                        <Icon name={group.icon ?? 'bs.gear'} className="text-xl" />
                    </span>
                    <span className="min-w-0">
                        <span className="block font-medium text-gray-900 group-hover:text-orbit-primary dark:text-gray-100">
                            {group.title}
                        </span>
                        {group.description ? (
                            <span className="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">
                                {group.description}
                            </span>
                        ) : null}
                    </span>
                </Link>
            ))}
        </div>
    );
}
