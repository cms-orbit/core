import type { FieldComponentProps } from '../contract';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr, str } from './shared';

export function RangeField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const min = Number(attr(props, 'min') ?? 0);
    const max = Number(attr(props, 'max') ?? 100);
    const step = Number(attr(props, 'step') ?? 1);

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="flex items-center gap-3">
                <input
                    type="range"
                    name={name ?? undefined}
                    min={min}
                    max={max}
                    step={step}
                    value={Number(value ?? min)}
                    onChange={(event) => onChange?.(Number(event.target.value))}
                    className="h-2 flex-1 cursor-pointer accent-orbit-primary"
                    disabled={Boolean(attr(props, 'disabled'))}
                />
                <span className="w-12 text-right text-sm tabular-nums text-gray-600 dark:text-gray-300">
                    {str(value ?? min)}
                </span>
            </div>
        </FieldShell>
    );
}

interface RangeValue {
    min?: number | string;
    max?: number | string;
}

function readRange(value: unknown): RangeValue {
    if (value && typeof value === 'object') {
        return value as RangeValue;
    }

    return {};
}

export function NumberRangeField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const current = readRange(value);
    const fieldMin = attr(props, 'min');
    const fieldMax = attr(props, 'max');
    const step = attr(props, 'step');

    const update = (key: 'min' | 'max', next: string) => {
        onChange?.({ ...current, [key]: next === '' ? undefined : Number(next) });
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="flex items-center gap-2">
                <input
                    type="number"
                    name={name ? `${name}[min]` : undefined}
                    placeholder="Min"
                    className={inputClass}
                    min={fieldMin as number | undefined}
                    max={fieldMax as number | undefined}
                    step={step as number | undefined}
                    value={str(current.min)}
                    onChange={(event) => update('min', event.target.value)}
                />
                <span className="text-gray-400">–</span>
                <input
                    type="number"
                    name={name ? `${name}[max]` : undefined}
                    placeholder="Max"
                    className={inputClass}
                    min={fieldMin as number | undefined}
                    max={fieldMax as number | undefined}
                    step={step as number | undefined}
                    value={str(current.max)}
                    onChange={(event) => update('max', event.target.value)}
                />
            </div>
        </FieldShell>
    );
}
