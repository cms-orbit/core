import { createInertiaApp } from '@inertiajs/react';
import { StrictMode, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { CropperFieldClient } from '../../resources/js/fields/cropper.client';

function Fixture() {
    const [value, setValue] = useState<unknown>('');
    return <>
        <CropperFieldClient node={{ component: 'Cropper', name: 'image', value: null, attributes: {}, errors: [] }} data={{}}
            name="image" value={value} attributes={{ width: 128, height: 64 }} errors={[]} onChange={setValue} />
        <output data-testid="result">{String(value)}</output>
    </>;
}

void createInertiaApp({
    page: { component: 'Fixture', props: { errors: {} }, url: '/', version: null, clearHistory: false, encryptHistory: false, rescuedProps: [], flash: {}, rememberedState: {} },
    resolve: () => Fixture,
    setup: ({ el, App, props }) => createRoot(el!).render(<StrictMode><App {...props} /></StrictMode>),
});
