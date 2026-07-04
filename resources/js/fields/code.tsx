import { useEffect, useState, type ComponentType } from 'react';
import type { FieldComponentProps } from '../contract';
import { FieldShell, fieldInputClass } from '../ui/field-shell';
import { attr, str } from './shared';

function CodePlaceholder(props: FieldComponentProps) {
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
                style={{ minHeight: height, fontFamily: 'ui-monospace, monospace' }}
                value={str(value)}
                onChange={(event) => onChange?.(event.target.value)}
            />
        </FieldShell>
    );
}

/**
 * SSR-safe CodeMirror wrapper. The editor bundle loads only in the browser.
 */
export function CodeField(props: FieldComponentProps) {
    const [ClientField, setClientField] = useState<ComponentType<FieldComponentProps> | null>(null);

    useEffect(() => {
        void import('./code.client').then((module) => {
            setClientField(() => module.CodeFieldClient);
        });
    }, []);

    if (ClientField) {
        return <ClientField {...props} />;
    }

    return <CodePlaceholder {...props} />;
}
