import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import type { LayoutNode } from '../contract';

export interface ModalOpenConfig {
    title?: string;
    async?: boolean;
    /** Endpoint that returns serialized layout JSON for the modal body. */
    url?: string;
    /** Parameters POSTed to the async endpoint. */
    params?: Record<string, unknown>;
    /** Optional form submission endpoint for the apply button. */
    method?: string | null;
}

export interface ModalState {
    open: boolean;
    config: ModalOpenConfig;
}

interface ModalContextValue {
    openModal: (slug: string, config?: ModalOpenConfig) => void;
    closeModal: (slug: string) => void;
    getModalState: (slug: string) => ModalState;
}

const ModalContext = createContext<ModalContextValue | null>(null);

const CLOSED: ModalState = { open: false, config: {} };

export function ModalProvider({ children }: { children: ReactNode }) {
    const [states, setStates] = useState<Record<string, ModalState>>({});

    const openModal = useCallback((slug: string, config: ModalOpenConfig = {}) => {
        setStates((current) => ({ ...current, [slug]: { open: true, config } }));
    }, []);

    const closeModal = useCallback((slug: string) => {
        setStates((current) => ({ ...current, [slug]: { open: false, config: {} } }));
    }, []);

    const getModalState = useCallback(
        (slug: string) => states[slug] ?? CLOSED,
        [states],
    );

    const value = useMemo<ModalContextValue>(
        () => ({ openModal, closeModal, getModalState }),
        [openModal, closeModal, getModalState],
    );

    return <ModalContext.Provider value={value}>{children}</ModalContext.Provider>;
}

export function useModal(): ModalContextValue {
    const context = useContext(ModalContext);

    if (!context) {
        throw new Error('useModal must be used within an Orbit <ModalProvider>.');
    }

    return context;
}

export function useOptionalModal(): ModalContextValue | null {
    return useContext(ModalContext);
}

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

interface AsyncLayoutResult {
    nodes: LayoutNode[] | null;
    data: Record<string, unknown>;
    loading: boolean;
    error: string | null;
}

/**
 * Fetches a serialized layout subtree from the `orbit.async` endpoint when a
 * modal opens. Replaces Orchid's Turbo Stream deferred loading with a plain
 * JSON request.
 */
export function useAsyncLayout(active: boolean, config: ModalOpenConfig): AsyncLayoutResult {
    const [result, setResult] = useState<AsyncLayoutResult>({
        nodes: null,
        data: {},
        loading: false,
        error: null,
    });

    const url = config.url;
    const paramsKey = JSON.stringify(config.params ?? {});

    useEffect(() => {
        if (!active || !config.async || !url) {
            return;
        }

        let cancelled = false;
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setResult({ nodes: null, data: {}, loading: true, error: null });

        const token = readCookie('XSRF-TOKEN');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-XSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
            body: paramsKey,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Request failed (${response.status})`);
                }

                return response.json();
            })
            .then((payload: { layout?: LayoutNode | LayoutNode[]; data?: Record<string, unknown> }) => {
                if (cancelled) {
                    return;
                }

                const layout = payload.layout;
                const nodes = Array.isArray(layout) ? layout : layout ? [layout] : [];

                setResult({ nodes, data: payload.data ?? {}, loading: false, error: null });
            })
            .catch((reason: unknown) => {
                if (cancelled) {
                    return;
                }

                const message = reason instanceof Error ? reason.message : 'Failed to load content';
                setResult({ nodes: null, data: {}, loading: false, error: message });
            });

        return () => {
            cancelled = true;
        };
    }, [active, config.async, url, paramsKey]);

    return result;
}
