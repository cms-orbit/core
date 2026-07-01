import { css } from '@codemirror/lang-css';
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { json } from '@codemirror/lang-json';
import type { Extension } from '@codemirror/state';
import CodeMirror from '@uiw/react-codemirror';
import { useMemo } from 'react';
import type { FieldComponentProps } from '../contract';
import { FieldShell } from '../ui/field-shell';
import { attr, bool, str } from './shared';

function languageExtension(language: string): Extension[] {
    switch (language) {
        case 'markup':
        case 'html':
        case 'xml':
        case 'svg':
            return [html()];
        case 'css':
            return [css()];
        case 'json':
            return [json()];
        case 'js':
        case 'javascript':
        case 'clike':
        default:
            return [javascript()];
    }
}

/** Code editor backed by CodeMirror 6 (replaces Orchid's CodeMirror field). */
export function CodeField(props: FieldComponentProps) {
    const { value, errors, onChange } = props;
    const language = str(attr(props, 'language') ?? 'js');
    const height = attr<string>(props, 'height') ?? '300px';
    const lineNumbers = attr(props, 'lineNumbers') !== false;
    const readOnly = bool(attr(props, 'readonly'));

    const extensions = useMemo(() => languageExtension(language), [language]);

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="overflow-hidden rounded-md border border-gray-300 dark:border-gray-700">
                <CodeMirror
                    value={str(value)}
                    height={height}
                    extensions={extensions}
                    editable={!readOnly}
                    basicSetup={{ lineNumbers }}
                    onChange={(next) => onChange?.(next)}
                />
            </div>
        </FieldShell>
    );
}
