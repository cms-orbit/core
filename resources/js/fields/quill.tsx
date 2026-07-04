import { useEffect, useState, type ComponentType } from 'react';
import type { FieldComponentProps } from '../contract';
import { FieldShell, fieldInputClass } from '../ui/field-shell';
import { attr, str } from './shared';

function QuillPlaceholder(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const height = attr<string>(props, 'height') ?? '300px';

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
                className={fieldInputClass(Boolean(errors[0]))}
                style={{ minHeight: height }}
                placeholder={attr<string>(props, 'placeholder')}
                value={str(value)}
                onChange={(event) => onChange?.(event.target.value)}
            />
        </FieldShell>
    );
}

/**
 * SSR-safe Quill wrapper. The Quill bundle loads only in the browser.
 */
export function QuillField(props: FieldComponentProps) {
    const [ClientField, setClientField] = useState<ComponentType<FieldComponentProps> | null>(null);

    useEffect(() => {
        void import('./quill.client').then((module) => {
            setClientField(() => module.QuillFieldClient);
        });
    }, []);

    if (ClientField) {
        return <ClientField {...props} />;
    }

    return <QuillPlaceholder {...props} />;
}
