import type { FieldNode, LayoutComponentProps } from '../contract';
import { useOptionalOrbitForm } from '../form-context';
import { ActionRenderer, LayoutChildren, LayoutNodeRenderer } from '../screen-renderer';
import { UiButton } from '../ui/button';
import { useAsyncLayout, useModal } from '../ui/modal';
import type { OverlaySize } from '../ui/overlay';
import { Overlay } from '../ui/overlay';

function mapSize(size: unknown): OverlaySize {
    if (size === 'modal-xl') {
        return 'xl';
    }

    if (size === 'modal-lg') {
        return 'lg';
    }

    if (size === 'modal-sm') {
        return 'sm';
    }

    return 'md';
}

function asActions(value: unknown): FieldNode[] {
    return Array.isArray(value) ? (value as FieldNode[]) : [];
}

/**
 * Server-driven modal. Open state is managed by ModalProvider (toggled by
 * ModalToggle actions). Async modals fetch their body from the `orbit.async`
 * endpoint; static modals render their serialized children.
 */
export function ModalLayout({ node, data, screen }: LayoutComponentProps) {
    const slug = (node.data.key as string) ?? node.key;
    const { getModalState, closeModal } = useModal();
    const form = useOptionalOrbitForm();

    const state = getModalState(slug);
    const open = state.open || Boolean(node.data.open);

    const serverParams = (node.data.deferrerParams as Record<string, unknown> | undefined) ?? {};
    const isAsync =
        state.config.async ?? (Boolean(node.data.deferredRoute) && Object.keys(serverParams).length > 0);

    const async = useAsyncLayout(open, {
        async: isAsync,
        url: state.config.url ?? (node.data.deferredRoute as string | undefined),
        params: { ...serverParams, ...(state.config.params ?? {}) },
    });

    const commandBar = asActions(node.data.commandBar);
    const applyMethod = state.config.method ?? (node.data.method as string | null | undefined);

    const handleApply = () => {
        if (form && applyMethod) {
            form.submit('post', applyMethod);
        }

        closeModal(slug);
    };

    const footer = (
        <>
            {commandBar.map((action, index) => (
                <ActionRenderer
                    key={action.name ?? `${action.component}-${index}`}
                    node={action}
                    data={data}
                    screen={screen}
                />
            ))}
            {!node.data.withoutCloseButton ? (
                <UiButton type="button" variant="default" onClick={() => closeModal(slug)}>
                    {(node.data.close as string) ?? 'Close'}
                </UiButton>
            ) : null}
            {!node.data.withoutApplyButton ? (
                <UiButton type="button" variant="primary" onClick={handleApply}>
                    {(node.data.apply as string) ?? 'Apply'}
                </UiButton>
            ) : null}
        </>
    );

    return (
        <Overlay
            open={open}
            onClose={() => closeModal(slug)}
            size={mapSize(node.data.size)}
            placement={node.data.type === 'slide-right' ? 'right' : 'center'}
            staticBackdrop={Boolean(node.data.staticBackdrop)}
            title={(node.data.title as string) ?? 'Modal'}
            footer={footer}
        >
            {isAsync ? (
                async.loading ? (
                    <ModalSkeleton />
                ) : async.error ? (
                    <p className="text-sm text-red-600">{async.error}</p>
                ) : async.nodes ? (
                    async.nodes.map((child) => (
                        <LayoutNodeRenderer
                            key={child.key}
                            node={child}
                            data={{ ...data, ...async.data }}
                            screen={screen}
                        />
                    ))
                ) : null
            ) : (
                <LayoutChildren nodes={node.children} data={data} screen={screen} className="space-y-4" />
            )}
        </Overlay>
    );
}

function ModalSkeleton() {
    return (
        <div className="space-y-3">
            {Array.from({ length: 3 }).map((_, index) => (
                <div key={index} className="h-9 animate-pulse rounded bg-gray-100 dark:bg-gray-700" />
            ))}
        </div>
    );
}
