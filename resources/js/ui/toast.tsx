import { usePage } from '@inertiajs/react';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface ToastOptions {
    type?: ToastType;
    title?: string;
    duration?: number;
}

interface ToastEntry extends ToastOptions {
    id: number;
    message: string;
}

interface ToastContextValue {
    toast: (message: string, options?: ToastOptions) => void;
    success: (message: string, options?: ToastOptions) => void;
    error: (message: string, options?: ToastOptions) => void;
    warning: (message: string, options?: ToastOptions) => void;
    info: (message: string, options?: ToastOptions) => void;
    dismiss: (id: number) => void;
}

const ToastContext = createContext<ToastContextValue | null>(null);

interface FlashShape {
    message?: string | null;
    type?: string | null;
}

interface ToastPageProps {
    orbit?: { flash?: FlashShape | null };
    flash?: FlashShape | null;
    [key: string]: unknown;
}

const typeStyle: Record<ToastType, string> = {
    success: 'border-green-200 bg-green-50 text-green-800 dark:border-green-500/40 dark:bg-green-500/10 dark:text-green-300',
    error: 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300',
    warning: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300',
    info: 'border-gray-200 bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100',
};

function normalizeType(type?: string | null): ToastType {
    if (type === 'success' || type === 'error' || type === 'warning' || type === 'info') {
        return type;
    }

    if (type === 'danger') {
        return 'error';
    }

    return 'info';
}

export function ToastProvider({ children }: { children: ReactNode }) {
    const [toasts, setToasts] = useState<ToastEntry[]>([]);
    const counter = useRef(0);

    const dismiss = useCallback((id: number) => {
        setToasts((current) => current.filter((entry) => entry.id !== id));
    }, []);

    const push = useCallback(
        (message: string, options?: ToastOptions) => {
            if (!message) {
                return;
            }

            const id = (counter.current += 1);
            const entry: ToastEntry = {
                id,
                message,
                type: options?.type ?? 'info',
                title: options?.title,
                duration: options?.duration ?? 5000,
            };

            setToasts((current) => [...current, entry]);

            if (entry.duration && entry.duration > 0) {
                window.setTimeout(() => dismiss(id), entry.duration);
            }
        },
        [dismiss],
    );

    const value = useMemo<ToastContextValue>(
        () => ({
            toast: push,
            success: (message, options) => push(message, { ...options, type: 'success' }),
            error: (message, options) => push(message, { ...options, type: 'error' }),
            warning: (message, options) => push(message, { ...options, type: 'warning' }),
            info: (message, options) => push(message, { ...options, type: 'info' }),
            dismiss,
        }),
        [push, dismiss],
    );

    return (
        <ToastContext.Provider value={value}>
            {children}
            <FlashBridge push={push} />
            <ToastViewport toasts={toasts} onDismiss={dismiss} />
        </ToastContext.Provider>
    );
}

/** Consumes Inertia shared flash data and surfaces it as toasts. */
function FlashBridge({ push }: { push: (message: string, options?: ToastOptions) => void }) {
    const page = usePage<ToastPageProps>();
    const flash = page.props.orbit?.flash ?? page.props.flash ?? null;
    const lastMessage = useRef<string | null>(null);

    useEffect(() => {
        const message = flash?.message ?? null;

        if (message && message !== lastMessage.current) {
            lastMessage.current = message;
            push(message, { type: normalizeType(flash?.type) });
        }

        if (!message) {
            lastMessage.current = null;
        }
    }, [flash, push]);

    return null;
}

function ToastViewport({
    toasts,
    onDismiss,
}: {
    toasts: ToastEntry[];
    onDismiss: (id: number) => void;
}) {
    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6">
            {toasts.map((entry) => (
                <div
                    key={entry.id}
                    className={cn(
                        'pointer-events-auto w-full max-w-sm rounded-lg border px-4 py-3 text-sm shadow-lg',
                        typeStyle[entry.type ?? 'info'],
                    )}
                    role="status"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            {entry.title ? (
                                <p className="font-semibold">{entry.title}</p>
                            ) : null}
                            <p>{entry.message}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() => onDismiss(entry.id)}
                            className="text-current/60 hover:text-current"
                            aria-label="Dismiss"
                        >
                            &times;
                        </button>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function useToast(): ToastContextValue {
    const context = useContext(ToastContext);

    if (!context) {
        throw new Error('useToast must be used within an Orbit <ToastProvider>.');
    }

    return context;
}

/** Non-throwing variant for components that may render outside the provider. */
export function useOptionalToast(): ToastContextValue | null {
    return useContext(ToastContext);
}
