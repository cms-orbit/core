import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import { UiButton } from './button';
import { Overlay } from './overlay';

export interface ConfirmOptions {
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    /** Renders the confirm action as a destructive (red) button. */
    danger?: boolean;
}

type ConfirmFn = (options?: ConfirmOptions) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);

interface PendingConfirm extends ConfirmOptions {
    resolve: (value: boolean) => void;
}

export function ConfirmProvider({ children }: { children: ReactNode }) {
    const [pending, setPending] = useState<PendingConfirm | null>(null);
    const pendingRef = useRef<PendingConfirm | null>(null);

    const settle = useCallback((value: boolean) => {
        const current = pendingRef.current;

        if (current) {
            current.resolve(value);
        }

        pendingRef.current = null;
        setPending(null);
    }, []);

    const confirm = useCallback<ConfirmFn>((options) => {
        return new Promise<boolean>((resolve) => {
            const entry: PendingConfirm = { ...options, resolve };
            pendingRef.current = entry;
            setPending(entry);
        });
    }, []);

    return (
        <ConfirmContext.Provider value={confirm}>
            {children}
            <Overlay
                open={pending !== null}
                onClose={() => settle(false)}
                size="sm"
                title={pending?.title ?? 'Please confirm'}
                footer={
                    <>
                        <UiButton type="button" variant="default" onClick={() => settle(false)}>
                            {pending?.cancelLabel ?? 'Cancel'}
                        </UiButton>
                        <UiButton
                            type="button"
                            variant={pending?.danger ? 'danger' : 'primary'}
                            onClick={() => settle(true)}
                        >
                            {pending?.confirmLabel ?? 'Confirm'}
                        </UiButton>
                    </>
                }
            >
                <p className="text-sm" style={{ color: 'var(--color-orbit-secondary, #475569)' }}>
                    {pending?.message ?? 'Are you sure you want to continue?'}
                </p>
            </Overlay>
        </ConfirmContext.Provider>
    );
}

/**
 * Returns a promise-based confirm() helper. Falls back to window.confirm when
 * used outside the provider so actions keep working everywhere.
 */
export function useConfirm(): ConfirmFn {
    const context = useContext(ConfirmContext);

    return useMemo<ConfirmFn>(() => {
        if (context) {
            return context;
        }

        return (options) =>
            Promise.resolve(window.confirm(options?.message ?? 'Are you sure?'));
    }, [context]);
}
