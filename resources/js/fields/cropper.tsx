import { useEffect, useState } from 'react';
import type { ComponentType } from 'react';
import type { FieldComponentProps } from '../contract';
import { useT } from '../lib/i18n';
import { FieldShell } from '../ui/field-shell';
import { attr, str } from './shared';

function CropperPlaceholder(props: FieldComponentProps) {
    const t = useT();
    const { value, errors } = props;
    const target = attr<string>(props, 'target') ?? 'url';
    const preview = attr<string>(props, 'url') ?? (target === 'url' ? str(value) : '');

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="flex h-28 w-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                {preview ? (
                    <img src={preview} alt="" className="h-full w-full object-cover" />
                ) : (
                    <span className="text-xs text-gray-400">{t('Image cropper loads in the browser.')}</span>
                )}
            </div>
        </FieldShell>
    );
}

/**
 * SSR-safe Cropper.js wrapper. The cropper bundle loads only in the browser.
 */
export function CropperField(props: FieldComponentProps) {
    const [ClientField, setClientField] = useState<ComponentType<FieldComponentProps> | null>(null);

    useEffect(() => {
        void import('./cropper.client').then((module) => {
            setClientField(() => module.CropperFieldClient);
        });
    }, []);

    if (ClientField) {
        return <ClientField {...props} />;
    }

    return <CropperPlaceholder {...props} />;
}
