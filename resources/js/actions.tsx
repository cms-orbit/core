import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FieldComponentProps, FieldNode } from './contract';
import { useOptionalOrbitForm } from './form-context';
import { cn } from './lib/cn';
import { MissingComponent } from './registry';
import type { ActionComponent } from './registry';
import { UiButton } from './ui/button';
import { useConfirm } from './ui/confirm';
import { Icon } from './ui/icon';
import { useModal } from './ui/modal';

function label(props: FieldComponentProps): string {
    return (props.attributes['name'] as string) ?? (props.name ?? '');
}

function iconName(attributes: Record<string, unknown>): string | null {
    const value = attributes['icon'];

    return typeof value === 'string' && value !== '' ? value : null;
}

function bool(value: unknown): boolean {
    return value === true || value === 'true' || value === 1 || value === '1';
}

function variantFromClass(className: unknown): 'primary' | 'default' | 'danger' | 'link' {
    const value = typeof className === 'string' ? className : '';

    if (value.includes('btn-danger')) {
        return 'danger';
    }

    if (value.includes('btn-primary')) {
        return 'primary';
    }

    if (value.includes('btn-link')) {
        return 'link';
    }

    return 'default';
}

export function ButtonAction(props: FieldComponentProps) {
    const form = useOptionalOrbitForm();
    const confirm = useConfirm();
    const [loading, setLoading] = useState(false);

    const action = props.attributes['action'] as string | undefined;
    const confirmMessage = props.attributes['confirm'] as string | undefined;

    const handleClick = async () => {
        if (confirmMessage) {
            const confirmed = await confirm({ message: confirmMessage, danger: variantFromClass(props.attributes['class']) === 'danger' });

            if (!confirmed) {
                return;
            }
        }

        if (!action) {
            return;
        }

        setLoading(true);

        const finish = { onFinish: () => setLoading(false) };

        if (form) {
            form.submit('post', action);
            setLoading(false);
        } else {
            router.post(action, {}, finish);
        }
    };

    return (
        <UiButton
            type="button"
            variant={variantFromClass(props.attributes['class'])}
            disabled={Boolean(props.attributes['disabled']) || loading || form?.processing}
            onClick={handleClick}
        >
            {loading ? <Spinner /> : <Icon name={iconName(props.attributes)} />}
            {label(props)}
        </UiButton>
    );
}

export function LinkAction(props: FieldComponentProps) {
    const href = (props.attributes['action'] as string | undefined) ?? (props.attributes['href'] as string | undefined) ?? '#';
    const downloadLink = Boolean(props.attributes['download']);
    const className = 'inline-flex items-center gap-2 text-sm text-orbit-primary hover:underline';

    if (downloadLink) {
        return (
            <a href={href} className={className} download>
                <Icon name={iconName(props.attributes)} />
                {label(props)}
            </a>
        );
    }

    return (
        <Link href={href} className={className}>
            <Icon name={iconName(props.attributes)} />
            {label(props)}
        </Link>
    );
}

export function ModalToggleAction(props: FieldComponentProps) {
    const { openModal } = useModal();
    const modal = props.attributes['modal'] as string | undefined;
    const async = bool(props.attributes['async']);
    const action = props.attributes['action'] as string | undefined;
    const method = props.attributes['method'] as string | null | undefined;
    const params = (props.attributes['parameters'] as Record<string, unknown> | undefined) ?? {};
    const title = props.attributes['modalTitle'] as string | undefined;

    const handleClick = () => {
        if (!modal) {
            return;
        }

        openModal(modal, { title, async, url: action ?? undefined, params, method });
    };

    return (
        <UiButton type="button" variant={variantFromClass(props.attributes['class'])} onClick={handleClick}>
            <Icon name={iconName(props.attributes)} />
            {label(props)}
        </UiButton>
    );
}

export function DropDownAction(props: FieldComponentProps) {
    const list = (props.attributes['list'] as FieldNode[] | undefined) ?? [];
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handler = (event: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handler);

        return () => document.removeEventListener('mousedown', handler);
    }, [open]);

    return (
        <div ref={containerRef} className="relative inline-block">
            <UiButton type="button" variant="default" onClick={() => setOpen((value) => !value)}>
                {label(props) || '⋯'}
            </UiButton>
            {open ? (
                <div className="absolute right-0 z-30 mt-1 min-w-44 rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    {list.map((item, index) => (
                        <DropDownItem key={item.name ?? `${item.component}-${index}`} node={item} data={props.data} />
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function DropDownItem({ node, data }: { node: FieldNode; data: Record<string, unknown> }) {
    const href = (node.attributes['action'] as string | undefined) ?? (node.attributes['href'] as string | undefined);
    const text = (node.attributes['name'] as string) ?? node.name ?? '';
    const itemClass =
        'flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700';
    const icon = <Icon name={iconName(node.attributes)} />;

    if (node.component === 'button') {
        const action = node.attributes['action'] as string | undefined;

        return (
            <button type="button" className={itemClass} onClick={() => action && router.post(action)}>
                {icon}
                {text}
            </button>
        );
    }

    void data;

    return (
        <Link href={href ?? '#'} className={itemClass}>
            {icon}
            {text}
        </Link>
    );
}

export function MenuAction(props: FieldComponentProps) {
    const href = props.attributes['href'] as string | undefined;
    const title = (props.attributes['title'] as string) ?? label(props);
    const list = (props.attributes['list'] as FieldNode[] | undefined) ?? [];
    const divider = bool(props.attributes['divider']);

    if (divider) {
        return <hr className="my-2 border-gray-100 dark:border-gray-800" />;
    }

    if (list.length > 0) {
        return <DropDownAction {...props} />;
    }

    return (
        <Link
            href={href ?? '#'}
            className={cn(
                'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm',
                bool(props.attributes['active'])
                    ? 'bg-orbit-primary/10 font-medium text-orbit-primary'
                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
            )}
        >
            <Icon name={iconName(props.attributes)} />
            {title}
        </Link>
    );
}

function Spinner() {
    return (
        <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
        </svg>
    );
}

function placeholder(name: string): ActionComponent {
    function ActionPlaceholder() {
        return <MissingComponent kind="action" name={name} />;
    }
    ActionPlaceholder.displayName = `ActionPlaceholder(${name})`;

    return ActionPlaceholder;
}

/** All action registry slots. */
export const actionComponents: Record<string, ActionComponent> = {
    button: ButtonAction,
    link: LinkAction,
    'drop-down': DropDownAction,
    'modal-toggle': ModalToggleAction,
    toggle: placeholder('toggle'),
    menu: MenuAction,
};
