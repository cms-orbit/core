import type { ReactNode } from 'react';
import { ConfirmProvider } from './confirm';
import { ModalProvider } from './modal';
import { ToastProvider } from './toast';

/**
 * Wraps the admin shell with the shared UI primitive providers (toast, confirm,
 * modal). Packages and host apps can rely on the corresponding hooks anywhere
 * inside the Orbit dashboard.
 */
export function OrbitProviders({ children }: { children: ReactNode }) {
    return (
        <ToastProvider>
            <ConfirmProvider>
                <ModalProvider>{children}</ModalProvider>
            </ConfirmProvider>
        </ToastProvider>
    );
}
