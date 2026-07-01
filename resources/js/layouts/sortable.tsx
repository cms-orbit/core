import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ColumnNode, LayoutComponentProps } from '../contract';
import { UiButton } from '../ui/button';
import { Card, CardBody, CardHeader } from '../ui/card';

type Row = Record<string, unknown>;

function asColumns(value: unknown): ColumnNode[] {
    return Array.isArray(value) ? (value as ColumnNode[]) : [];
}

function asRows(value: unknown): Row[] {
    if (Array.isArray(value)) {
        return value as Row[];
    }

    if (value && typeof value === 'object' && Array.isArray((value as { data?: unknown }).data)) {
        return (value as { data: Row[] }).data;
    }

    return [];
}

/**
 * Drag-to-reorder list. Persists the new order via an Inertia POST to
 * `node.data.sortUrl` (defaults to the current path), sending ordered ids.
 * The backend serializes `rows`, `columns` and optional `sortUrl`.
 */
export function SortableLayout({ node, data }: LayoutComponentProps) {
    const columns = asColumns(node.data.columns);
    const target = node.data.target as string | undefined;
    const initialRows = asRows(node.data.rows ?? (target ? data[target] : undefined));

    const [rows, setRows] = useState<Row[]>(initialRows);
    const [dragIndex, setDragIndex] = useState<number | null>(null);
    const [dirty, setDirty] = useState(false);

    useEffect(() => {
        // Reset when the serialized rows change.
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setRows(initialRows);
        setDirty(false);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [JSON.stringify(initialRows)]);

    const move = (from: number, to: number) => {
        if (from === to) {
            return;
        }

        setRows((current) => {
            const next = [...current];
            const [moved] = next.splice(from, 1);
            next.splice(to, 0, moved);

            return next;
        });
        setDirty(true);
    };

    const persist = () => {
        const sortUrl = (node.data.sortUrl as string | undefined) ?? window.location.pathname;
        const order = rows.map((row) => String(row.id ?? row.key ?? ''));
        router.post(sortUrl, { sort: order }, { preserveScroll: true, onSuccess: () => setDirty(false) });
    };

    return (
        <Card>
            <CardHeader title={node.data.title as string | null}>
                {dirty ? (
                    <UiButton type="button" variant="primary" onClick={persist}>
                        Save order
                    </UiButton>
                ) : null}
            </CardHeader>
            <CardBody className="p-0">
                {rows.length === 0 ? (
                    <p className="px-5 py-8 text-center text-sm text-gray-400">
                        {(node.data.textNotFound as string) ?? 'Nothing to sort.'}
                    </p>
                ) : (
                    <ul className="divide-y divide-gray-100 dark:divide-gray-800">
                        {rows.map((row, index) => (
                            <li
                                key={String(row.id ?? row.key ?? index)}
                                draggable
                                onDragStart={() => setDragIndex(index)}
                                onDragOver={(event) => event.preventDefault()}
                                onDrop={() => {
                                    if (dragIndex !== null) {
                                        move(dragIndex, index);
                                    }

                                    setDragIndex(null);
                                }}
                                className="flex cursor-move items-center gap-3 px-5 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <span className="text-gray-300">⋮⋮</span>
                                {columns.map((column) => (
                                    <span key={column.slug} className="text-gray-700 dark:text-gray-200">
                                        {column.rendered != null ? (
                                            <span dangerouslySetInnerHTML={{ __html: column.rendered }} />
                                        ) : (
                                            String(row[column.name] ?? '')
                                        )}
                                    </span>
                                ))}
                            </li>
                        ))}
                    </ul>
                )}
            </CardBody>
        </Card>
    );
}
