import type { FieldComponentProps } from '../contract';
import { UiButton } from '../ui/button';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr, str } from './shared';

type MatrixRow = Record<string, unknown>;

function readRows(value: unknown): MatrixRow[] {
    if (Array.isArray(value)) {
        return value as MatrixRow[];
    }

    if (value && typeof value === 'object') {
        return Object.values(value as Record<string, MatrixRow>);
    }

    return [];
}

/** Repeatable key/value (or multi-column) matrix editor. */
export function MatrixField(props: FieldComponentProps) {
    const { errors, onChange } = props;
    const columns = (attr(props, 'columns') as string[] | undefined) ?? ['key', 'value'];
    const maxRows = Number(attr(props, 'maxRows') ?? 0);
    const removableRows = attr(props, 'removableRows') !== false;
    const addRowLabel = attr<string>(props, 'addRowLabel') ?? 'Add row';
    const rows = readRows(props.value);

    const commit = (next: MatrixRow[]) => onChange?.(next);

    const updateCell = (rowIndex: number, column: string, cellValue: string) => {
        const next = rows.map((row, index) =>
            index === rowIndex ? { ...row, [column]: cellValue } : row,
        );
        commit(next);
    };

    const addRow = () => {
        if (maxRows > 0 && rows.length >= maxRows) {
            return;
        }

        const blank: MatrixRow = {};
        columns.forEach((column) => {
            blank[column] = '';
        });
        commit([...rows, blank]);
    };

    const removeRow = (rowIndex: number) => {
        commit(rows.filter((_, index) => index !== rowIndex));
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            error={errors[0]}
        >
            <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column}
                                    className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    {column}
                                </th>
                            ))}
                            {removableRows ? <th className="w-10" /> : null}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                        {rows.map((row, rowIndex) => (
                            <tr key={rowIndex}>
                                {columns.map((column) => (
                                    <td key={column} className="px-2 py-1">
                                        <input
                                            className={inputClass}
                                            value={str(row[column])}
                                            onChange={(event) => updateCell(rowIndex, column, event.target.value)}
                                        />
                                    </td>
                                ))}
                                {removableRows ? (
                                    <td className="px-2 py-1 text-center">
                                        <button
                                            type="button"
                                            onClick={() => removeRow(rowIndex)}
                                            className="text-gray-400 hover:text-red-600"
                                            aria-label="Remove row"
                                        >
                                            &times;
                                        </button>
                                    </td>
                                ) : null}
                            </tr>
                        ))}
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length + 1} className="px-3 py-4 text-center text-sm text-gray-400">
                                    No rows yet.
                                </td>
                            </tr>
                        ) : null}
                    </tbody>
                </table>
            </div>
            <div className="mt-2">
                <UiButton
                    type="button"
                    variant="default"
                    onClick={addRow}
                    disabled={maxRows > 0 && rows.length >= maxRows}
                >
                    {addRowLabel}
                </UiButton>
            </div>
        </FieldShell>
    );
}
