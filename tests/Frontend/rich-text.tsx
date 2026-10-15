import { createInertiaApp } from '@inertiajs/react';
import { StrictMode, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { RichTextFieldClient } from '../../resources/js/fields/rich-text.client';

function Fixture() {
    const [value, setValue] = useState<unknown>('<p>Saved <strong>content</strong></p>');
    return <>
        <RichTextFieldClient node={{ component: 'RichText', name: 'content', value: null, attributes: {}, errors: [] }} data={{}}
            name="content" value={value} attributes={{}} errors={[]} onChange={setValue} />
        <output data-testid="result">{String(value)}</output>
    </>;
}

void createInertiaApp({
    page: { component: 'Fixture', props: { errors: {} }, url: '/', version: null, clearHistory: false, encryptHistory: false, rescuedProps: [], flash: {}, rememberedState: {} },
    resolve: () => Fixture,
    setup: ({ el, App, props }) => createRoot(el!).render(<StrictMode><App {...props} /></StrictMode>),
});
