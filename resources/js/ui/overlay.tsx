import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { cn } from '../lib/cn';

export type OverlaySize = 'sm' | 'md' | 'lg' | 'xl';
export type OverlayPlacement = 'center' | 'right';

const sizeClass: Record<OverlaySize, string> = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

/**
 * Low-level modal overlay primitive shared by useModal/useConfirm and the
 * server-driven Modal layout. Handles backdrop, escape key and focus trap-ish
 * behaviour without external dependencies.
 */
export function Overlay({
    open,
    onClose,
    size = 'md',
    placement = 'center',
    staticBackdrop = false,
    title,
    footer,
    children,
}: {
    open: boolean;
    onClose: () => void;
    size?: OverlaySize;
    placement?: OverlayPlacement;
    staticBackdrop?: boolean;
    title?: ReactNode;
    footer?: ReactNode;
    children: ReactNode;
}) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const handler = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && !staticBackdrop) {
                onClose();
            }
        };

        document.addEventListener('keydown', handler);

        return () => document.removeEventListener('keydown', handler);
    }, [open, onClose, staticBackdrop]);

    if (!open) {
        return null;
    }

    const panelPosition =
        placement === 'right'
            ? 'ml-auto h-full rounded-l-xl rounded-r-none'
            : 'mx-auto my-8 rounded-xl';

    return (
        <div
            className="fixed inset-0 z-50 flex bg-black/40 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget && !staticBackdrop) {
                    onClose();
                }
            }}
        >
            <div
                className={cn(
                    'flex max-h-[calc(100vh-2rem)] w-full flex-col bg-white shadow-xl dark:bg-gray-800',
                    placement === 'center' && sizeClass[size],
                    placement === 'right' && 'w-full sm:max-w-md',
                    panelPosition,
                )}
            >
                {title ? (
                    <div className="flex items-center justify-between border-b border-gray-100 px-5 py-3 dark:border-gray-700">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {title}
                        </h3>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                            aria-label="Close"
                        >
                            <CloseIcon />
                        </button>
                    </div>
                ) : null}

                <div className="flex-1 overflow-y-auto p-5">{children}</div>

                {footer ? (
                    <div className="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                        {footer}
                    </div>
                ) : null}
            </div>
        </div>
    );
}

function CloseIcon() {
    return (
        <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-4 w-4"
            aria-hidden="true"
        >
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 0 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
        </svg>
    );
}
