import type { FormDataConvertible } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useEffect, useMemo, useRef } from 'react';
import type { LayoutComponentProps } from '../contract';
import { useOptionalOrbitForm } from '../form-context';
import { LayoutChildren } from '../screen-renderer';

function parseTargets(raw: unknown): string[] {
    if (Array.isArray(raw)) {
        return raw.map(String);
    }

    if (typeof raw === 'string') {
        try {
            const parsed = JSON.parse(raw);

            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch {
            return raw ? [raw] : [];
        }
    }

    return [];
}

/**
 * Re-runs the server listener when a watched field changes by issuing an
 * Inertia partial reload scoped to this layout key (replaces Orchid's Turbo
 * listener). The backend serializes `targets` (watched field names).
 */
export function ListenerLayout({ node, data, screen }: LayoutComponentProps) {
    const form = useOptionalOrbitForm();
    const targets = useMemo(() => parseTargets(node.data.targets), [node.data.targets]);

    const watched = targets.map((target) => form?.getValue(target));
    const signature = JSON.stringify(watched);
    const previous = useRef<string | null>(null);

    useEffect(() => {
        if (previous.current === null) {
            previous.current = signature;

            return;
        }

        if (previous.current === signature || targets.length === 0) {
            return;
        }

        previous.current = signature;

        const payload: Record<string, FormDataConvertible> = {};
        targets.forEach((target, index) => {
            payload[target] = watched[index] as FormDataConvertible;
        });

        const timer = window.setTimeout(() => {
            router.reload({ only: [node.key], data: payload });
        }, 250);

        return () => window.clearTimeout(timer);
        // `watched` is captured through `signature`.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [signature, node.key]);

    return <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />;
}
