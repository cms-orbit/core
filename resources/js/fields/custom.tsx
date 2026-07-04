import type { CustomComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { FieldShell } from '../ui/field-shell';
import { str } from './shared';

/** Demo / escape-hatch color swatch picker (ReactField::component('ColorPicker')). */
export function ColorPickerField({
    value,
    onChange,
    props: customProps,
    attributes,
}: CustomComponentProps) {
    const palette = (customProps?.palette as string[] | undefined) ?? [
        '#17ce91',
        '#fc8024',
        '#64748b',
        '#3b82f6',
        '#ef4444',
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
