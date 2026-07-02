import type { FieldComponentProps } from './contract';
import {
    RadioButtonsField,
    RadioField,
    SelectField,
    TimeZoneField,
} from './fields/choice';
import { CodeField } from './fields/code';
import { CropperField } from './fields/cropper';
import { DateRangeField, DateTimerField } from './fields/date';
import { MapField } from './fields/map';
import { MarkdownField } from './fields/markdown';
import { MatrixField } from './fields/matrix';
import { GroupField, UtmField, ViewFieldField } from './fields/misc';
import { PermissionMatrixField } from './fields/permission-matrix';
import { QuillField } from './fields/quill';
import { NumberRangeField, RangeField } from './fields/range';
import { attr, str } from './fields/shared';
import { AttachField, PictureField } from './fields/upload';
import type { FieldComponent } from './registry';
import { FieldShell, fieldInputClass } from './ui/field-shell';

export function InputField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
            htmlFor={attr<string>(props, 'id')}
        >
            <input
                id={attr<string>(props, 'id')}
                name={name ?? undefined}
                type={attr<string>(props, 'type') ?? 'text'}
                placeholder={attr<string>(props, 'placeholder')}
                className={fieldInputClass(Boolean(errors[0]))}
                value={str(value)}
                onChange={(event) => onChange?.(event.target.value)}
            />
        </FieldShell>
    );
}

export function TextAreaField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
            htmlFor={attr<string>(props, 'id')}
        >
            <textarea
                id={attr<string>(props, 'id')}
                name={name ?? undefined}
                rows={attr<number>(props, 'rows') ?? 4}
                placeholder={attr<string>(props, 'placeholder')}
                className={fieldInputClass(Boolean(errors[0]))}
                value={str(value)}
                onChange={(event) => onChange?.(event.target.value)}
            />
        </FieldShell>
    );
}

export function CheckBoxField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;

    return (
        <FieldShell help={attr<string>(props, 'help')} error={errors[0]}>
            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input
                    name={name ?? undefined}
                    type="checkbox"
                    className="h-4 w-4 rounded border-gray-300 text-orbit-primary-600 focus:ring-2 focus:ring-orbit-primary-500/40 dark:border-white/20 dark:bg-gray-900"
                    checked={Boolean(value)}
                    onChange={(event) => onChange?.(event.target.checked)}
                />
                {attr<string>(props, 'placeholder') ?? attr<string>(props, 'title')}
            </label>
        </FieldShell>
    );
}

export function SwitcherField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;

    return (
        <FieldShell help={attr<string>(props, 'help')} error={errors[0]}>
            <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <span className="relative inline-flex h-5 w-9 items-center">
                    <input
                        name={name ?? undefined}
                        type="checkbox"
                        className="peer sr-only"
                        checked={Boolean(value)}
                        onChange={(event) => onChange?.(event.target.checked)}
                    />
                    <span className="absolute inset-0 rounded-full bg-gray-300 transition peer-checked:bg-orbit-primary-600 dark:bg-gray-600" />
                    <span className="absolute left-0.5 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-4" />
                </span>
                {attr<string>(props, 'placeholder') ?? attr<string>(props, 'title')}
            </label>
        </FieldShell>
    );
}

export function LabelField(props: FieldComponentProps) {
    return (
        <FieldShell title={attr<string>(props, 'title')} help={attr<string>(props, 'help')}>
            <p className="text-sm text-gray-900 dark:text-gray-100">{str(props.value)}</p>
        </FieldShell>
    );
}

export { SelectField };

/**
 * All field registry slots. Heavy widgets are backed by React ecosystem
 * libraries (Quill, CodeMirror 6, Leaflet, Cropper.js, marked).
 */
export const fieldComponents: Record<string, FieldComponent> = {
    input: InputField,
    password: InputField,
    'text-area': TextAreaField,
    select: SelectField,
    'check-box': CheckBoxField,
    switcher: SwitcherField,
    label: LabelField,
    radio: RadioField,
    'radio-buttons': RadioButtonsField,
    range: RangeField,
    'number-range': NumberRangeField,
    'date-timer': DateTimerField,
    'date-range': DateRangeField,
    'time-zone': TimeZoneField,
    attach: AttachField,
    picture: PictureField,
    cropper: CropperField,
    quill: QuillField,
    markdown: MarkdownField,
    'simple-m-d-e': MarkdownField,
    code: CodeField,
    matrix: MatrixField,
    'permission-matrix': PermissionMatrixField,
    map: MapField,
    utm: UtmField,
    group: GroupField,
    'view-field': ViewFieldField,
};
