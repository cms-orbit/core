import type { FieldComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr, bool, str } from './shared';

/** Map flatpickr-style attributes to a native input type. */
function resolveInputType(props: FieldComponentProps): 'date' | 'datetime-local' | 'time' {
    const enableTime = bool(attr(props, 'data-datetime-enable-time'));
    const noCalendar = bool(attr(props, 'data-datetime-no-calendar'));

    if (noCalendar) {
        return 'time';
    }

    return enableTime ? 'datetime-local' : 'date';
}

/** Best-effort conversion of an arbitrary date string to a native input value. */
function toNativeValue(value: unknown, type: string): string {
    const raw = str(value);

    if (!raw) {
        return '';
    }

    const parsed = new Date(raw.replace(' ', 'T'));

    if (Number.isNaN(parsed.getTime())) {
        return raw;
    }

    const pad = (n: number) => String(n).padStart(2, '0');
    const date = `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}`;
    const time = `${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`;

    if (type === 'time') {
        return time;
    }

    if (type === 'datetime-local') {
        return `${date}T${time}`;
    }

    return date;
}

export function DateTimerField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const type = resolveInputType(props);
    const quickDates = (attr(props, 'quickDates') as string[] | undefined) ?? [];
    const compact = bool(attr(props, 'compact'));

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
            compact={compact}
        >
            <input
                type={type}
                name={name ?? undefined}
                className={inputClass}
                placeholder={attr<string>(props, 'placeholder')}
                value={toNativeValue(value, type)}
                disabled={Boolean(attr(props, 'disabled'))}
                readOnly={Boolean(attr(props, 'readonly'))}
                onChange={(event) => onChange?.(event.target.value)}
            />
            {quickDates.length > 0 ? (
                <div className="mt-2 flex flex-wrap gap-1.5">
                    {quickDates.map((quick) => (
                        <button
                            key={quick}
                            type="button"
                            onClick={() => onChange?.(quick)}
                            className="rounded border border-gray-200 px-2 py-0.5 text-xs text-gray-600 hover:border-orbit-primary hover:text-orbit-primary dark:border-gray-700 dark:text-gray-300"
                        >
                            {quick}
                        </button>
                    ))}
                </div>
            ) : null}
        </FieldShell>
    );
}

interface DateRangeValue {
    start?: string;
    end?: string;
}

function readRange(value: unknown): DateRangeValue {
    if (Array.isArray(value)) {
        return { start: str(value[0]), end: str(value[1]) };
    }

    if (value && typeof value === 'object') {
        return value as DateRangeValue;
    }

    return {};
}

export function DateRangeField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const enableTime = bool(attr(props, 'data-datetime-enable-time'));
    const type = enableTime ? 'datetime-local' : 'date';
    const current = readRange(value);
    const compact = bool(attr(props, 'compact'));

    const update = (key: 'start' | 'end', next: string) => {
        onChange?.({ ...current, [key]: next });
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
            compact={compact}
        >
            <div className={cn('flex items-center gap-2', str(attr(props, 'layout')) === 'stack' && 'flex-col items-stretch')}>
                <input
                    type={type}
                    name={name ? `${name}[start]` : undefined}
                    className={inputClass}
                    value={toNativeValue(current.start, type)}
                    onChange={(event) => update('start', event.target.value)}
                />
                <span className="text-gray-400">–</span>
                <input
                    type={type}
                    name={name ? `${name}[end]` : undefined}
                    className={inputClass}
                    value={toNativeValue(current.end, type)}
                    onChange={(event) => update('end', event.target.value)}
                />
            </div>
        </FieldShell>
    );
}
