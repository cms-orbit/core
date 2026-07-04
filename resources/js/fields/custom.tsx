import { useState } from 'react';
import type { MouseEvent } from 'react';
import type { CustomComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { UiButton } from '../ui/button';
import { FieldShell } from '../ui/field-shell';
import { useOptionalToast } from '../ui/toast';
import { str } from './shared';

/** Demo / escape-hatch color swatch picker (ReactField::component('ColorPicker')). */
export function ColorPickerField({
    value,
    onChange,
    props: customProps,
    attributes,
}: CustomComponentProps) {
    const palette = (customProps?.palette as string[] | undefined) ?? [
        '#10b981',
        '#2563eb',
        '#4f46e5',
        '#ca8a04',
        '#475569',
    ];
    const selected = str(value);

    return (
        <FieldShell title={(attributes?.title as string | undefined) ?? undefined}>
            <div className="flex flex-wrap gap-2">
                {palette.map((color) => (
                    <button
                        key={color}
                        type="button"
                        aria-label={color}
                        title={color}
                        className={cn(
                            'h-9 w-9 rounded-full border-2 transition',
                            selected === color
                                ? 'border-orbit-primary-600 ring-2 ring-orbit-primary-500/30'
                                : 'border-gray-200 hover:border-gray-300 dark:border-white/20',
                        )}
                        style={{ backgroundColor: color }}
                        onClick={() => onChange?.(color)}
                    />
                ))}
            </div>
        </FieldShell>
    );
}

export function RoleIdCell({ value, props: customProps }: CustomComponentProps) {
    const t = useT();
    const toast = useOptionalToast();
    const [copied, setCopied] = useState(false);
    const fullId = str(customProps?.fullId ?? value);
    const shortId = fullId.length > 5 ? `${fullId.slice(0, 5)}...` : fullId;
    const copyLabel = str(customProps?.copyLabel) || t('Copy ID');
    const copiedLabel = str(customProps?.copiedLabel) || t('Copied');

    const markCopied = () => {
        setCopied(true);
        toast?.success(t('The ID was copied to the clipboard.'));
        window.setTimeout(() => setCopied(false), 1500);
    };

    const fallbackCopy = () => {
        if (typeof document === 'undefined') {
            return false;
        }

        const input = document.createElement('textarea');
        input.value = fullId;
        input.setAttribute('readonly', 'true');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        input.setSelectionRange(0, fullId.length);

        try {
            return document.execCommand('copy');
        } finally {
            document.body.removeChild(input);
        }
    };

    const copy = async (event: MouseEvent<HTMLButtonElement>) => {
        event.preventDefault();
        event.stopPropagation();

        if (!fullId) {
            return;
        }

        try {
            if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(fullId);
                markCopied();

                return;
            }

            if (fallbackCopy()) {
                markCopied();
                return;
            }
        } catch {
            if (fallbackCopy()) {
                markCopied();
                return;
            }
        }

        toast?.error(t('Unable to copy the ID automatically.'));
    };

    return (
        <div className="group relative flex h-8 w-full items-center">
            <button
                type="button"
                aria-label={fullId}
                className="w-full cursor-help truncate text-left font-mono text-xs text-gray-700 underline decoration-dotted underline-offset-2 dark:text-gray-200"
            >
                {shortId}
            </button>

            <div className="pointer-events-none absolute inset-0 z-10 opacity-0 transition group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                <div className="flex h-full items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-1 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div className="min-w-0 flex-1 overflow-x-auto">
                        <p className="whitespace-nowrap font-mono text-[10px] text-gray-700 dark:text-gray-200">
                            {fullId}
                        </p>
                    </div>
                    <UiButton
                        type="button"
                        aria-label={copied ? copiedLabel : copyLabel}
                        size="sm"
                        variant="ghost"
                        iconOnly
                        icon={copied ? 'bs.clipboard-check' : 'bs.clipboard'}
                        className="shrink-0"
                        onClick={copy}
                    />
                </div>
            </div>
        </div>
    );
}
