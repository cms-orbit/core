import { useCallback, useId, useRef, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { isTransparentColor, TRANSPARENT_COLOR } from '../lib/color-value';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { FieldShell } from '../ui/field-shell';
import { str } from './shared';

const HEX_PATTERN = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i;
const CHECKERBOARD =
    'linear-gradient(45deg, #cbd5e1 25%, transparent 25%), linear-gradient(-45deg, #cbd5e1 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #cbd5e1 75%), linear-gradient(-45deg, transparent 75%, #cbd5e1 75%)';

function normalizeHex(value: string): string {
    const trimmed = value.trim();

    if (!HEX_PATTERN.test(trimmed)) {
        return trimmed;
    }

    if (trimmed.length === 4) {
        const [, hex] = trimmed.match(/^#([0-9a-f]{3})$/i) ?? [];

        return `#${hex!.split('').map((c) => c + c).join('')}`;
    }

    return trimmed.toLowerCase();
}

function resolveDisplayColor(value: string): { color: string; transparent: boolean } {
    if (isTransparentColor(value)) {
        return { color: TRANSPARENT_COLOR, transparent: true };
    }

    return { color: normalizeHex(value || '#000000'), transparent: false };
}

/** Polished colour picker with swatch, hex input and native picker fallback. */
export function ColorField(props: FieldComponentProps) {
    const t = useT();
    const { name, value, errors, onChange } = props;
    const inputId = useId();
    const pickerRef = useRef<HTMLInputElement>(null);
    const rawValue = str(value);
    const { color, transparent } = resolveDisplayColor(rawValue);
    const [draft, setDraft] = useState(color);
    const [focused, setFocused] = useState(false);

    const commit = useCallback(
        (next: string) => {
            if (isTransparentColor(next)) {
                setDraft(TRANSPARENT_COLOR);
                onChange?.(TRANSPARENT_COLOR);

                return;
            }

            const normalized = normalizeHex(next);

            if (HEX_PATTERN.test(normalized)) {
                setDraft(normalized);
                onChange?.(normalized);
            }
        },
        [onChange],
    );

    const display = focused ? draft : color;

    return (
        <FieldShell
            title={props.attributes.title as string | undefined}
            help={props.attributes.help as string | undefined}
            required={props.attributes.required as boolean | undefined}
            error={errors[0]}
            htmlFor={inputId}
            className="mb-0"
        >
            <div
                className={cn(
                    'flex items-center gap-3 rounded-xl border bg-white p-2 transition dark:bg-gray-900',
                    errors[0]
                        ? 'border-red-400 ring-1 ring-red-400/30'
                        : 'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20',
                )}
            >
                <button
                    type="button"
                    aria-label={t('Open colour picker')}
                    className={cn(
                        'group relative h-10 w-10 shrink-0 overflow-hidden rounded-lg shadow-inner ring-1 ring-black/10 transition hover:scale-105 dark:ring-white/10',
                        transparent && 'bg-[length:8px_8px] bg-[position:0_0,0_4px,4px_-4px,-4px_0px]',
                    )}
                    style={
                        transparent
                            ? { backgroundImage: CHECKERBOARD }
                            : { backgroundColor: color }
                    }
                    onClick={() => {
                        if (!transparent) {
                            pickerRef.current?.click();
                        }
                    }}
                >
                    {!transparent ? (
                        <span className="absolute inset-0 bg-gradient-to-br from-white/25 to-transparent opacity-0 transition group-hover:opacity-100" />
                    ) : null}
                </button>

                <input
                    ref={pickerRef}
                    type="color"
                    className="sr-only"
                    value={HEX_PATTERN.test(color) ? color : '#000000'}
                    onChange={(event) => commit(event.target.value)}
                    tabIndex={-1}
                />

                <div className="min-w-0 flex-1">
                    <input
                        id={inputId}
                        name={name ?? undefined}
                        type="text"
                        value={display}
                        spellCheck={false}
                        placeholder="#000000"
                        className="w-full border-0 bg-transparent font-mono text-sm text-gray-900 outline-none placeholder:text-gray-400 dark:text-gray-100"
                        onFocus={() => {
                            setFocused(true);
                            setDraft(color);
                        }}
                        onBlur={() => {
                            setFocused(false);
                            commit(draft);
                        }}
                        onChange={(event) => setDraft(event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.currentTarget.blur();
                            }
                        }}
                    />
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        className={cn(
                            'rounded-md px-2 py-1 text-xs font-medium transition',
                            transparent
                                ? 'bg-orbit-primary/10 text-orbit-primary'
                                : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-gray-200',
                        )}
                        onClick={() => commit(TRANSPARENT_COLOR)}
                    >
                        {t('No color')}
                    </button>
                    <button
                        type="button"
                        className="rounded-md px-2 py-1 text-xs font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        onClick={() => pickerRef.current?.click()}
                    >
                        {t('Pick')}
                    </button>
                </div>
            </div>
        </FieldShell>
    );
}
