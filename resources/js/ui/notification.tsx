import { usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { cn } from '../lib/cn';

export interface OrbitNotification {
    id: string | number;
    title?: string | null;
    message: string;
    url?: string | null;
    time?: string | null;
    read?: boolean;
}

interface NotificationPageProps {
    orbit?: { notifications?: OrbitNotification[] };
    [key: string]: unknown;
}

/**
 * Bell + dropdown notification center. Reads the shared `orbit.notifications`
 * Inertia prop (Orchid Notification successor). Backend emits the list shape.
 */
export function NotificationCenter() {
    const page = usePage<NotificationPageProps>();
    const notifications = useMemo(
        () => page.props.orbit?.notifications ?? [],
        [page.props.orbit?.notifications],
    );
    const [open, setOpen] = useState(false);

    const unread = notifications.filter((item) => !item.read).length;

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="relative rounded-full p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                aria-label="Notifications"
            >
                <BellIcon />
                {unread > 0 ? (
                    <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-orbit-primary px-1 text-[10px] font-semibold text-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                ) : null}
            </button>

            {open ? (
                <>
                    <button
                        type="button"
                        aria-hidden="true"
                        tabIndex={-1}
                        className="fixed inset-0 z-30 cursor-default"
                        onClick={() => setOpen(false)}
                    />
                    <div className="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 dark:border-gray-700 dark:text-gray-100">
                            Notifications
                        </div>
                        <div className="max-h-96 overflow-y-auto">
                            {notifications.length === 0 ? (
                                <p className="px-4 py-6 text-center text-sm text-gray-400">
                                    You&apos;re all caught up.
                                </p>
                            ) : (
                                notifications.map((item) => (
                                    <a
                                        key={item.id}
                                        href={item.url ?? '#'}
                                        className={cn(
                                            'block border-b border-gray-50 px-4 py-3 text-sm last:border-b-0 hover:bg-gray-50 dark:border-gray-700/50 dark:hover:bg-gray-700/50',
                                            !item.read && 'bg-orbit-primary/5',
                                        )}
                                    >
                                        {item.title ? (
                                            <p className="font-medium text-gray-900 dark:text-gray-100">
                                                {item.title}
                                            </p>
                                        ) : null}
                                        <p className="text-gray-600 dark:text-gray-300">{item.message}</p>
                                        {item.time ? (
                                            <p className="mt-1 text-xs text-gray-400">{item.time}</p>
                                        ) : null}
                                    </a>
                                ))
                            )}
                        </div>
                    </div>
                </>
            ) : null}
        </div>
    );
}

function BellIcon() {
    return (
        <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5" aria-hidden="true">
            <path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6Zm0 16a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 10 18Z" />
        </svg>
    );
}
