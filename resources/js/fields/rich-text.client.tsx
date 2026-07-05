import { BlockNoteView } from '@blocknote/mantine';
import { useCreateBlockNote } from '@blocknote/react';
import '@blocknote/mantine/style.css';
import { useEffect, useRef, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { FieldShell } from '../ui/field-shell';
import { attr, str } from './shared';

function resolveEditorTheme(): 'light' | 'dark' {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

/** Browser-only BlockNote rich text editor. Persists HTML via blocksToHTMLLossy(). */
export function RichTextFieldClient(props: FieldComponentProps) {
    const t = useT();
    const { value, errors, onChange } = props;
    const onChangeRef = useRef(onChange);
    const loadedRef = useRef(false);
    const [theme, setTheme] = useState<'light' | 'dark'>(resolveEditorTheme);
    const [copyState, setCopyState] = useState<'idle' | 'copied' | 'failed'>('idle');

    const height = attr<string>(props, 'height') ?? '300px';
    const readOnly = Boolean(attr(props, 'readonly') || attr(props, 'disabled'));
    const placeholder = attr<string>(props, 'placeholder');

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setTheme(resolveEditorTheme());
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        return () => observer.disconnect();
    }, []);

    const editor = useCreateBlockNote({
        ...(placeholder ? { placeholders: { default: placeholder } } : {}),
    });

    useEffect(() => {
        if (loadedRef.current) {
            return;
        }

        const html = str(value);

        if (html !== '') {
            const blocks = editor.tryParseHTMLToBlocks(html);
            editor.replaceBlocks(editor.document, blocks);
        }

        loadedRef.current = true;
    }, [editor, value]);

    useEffect(() => {
        return editor.onChange(() => {
            onChangeRef.current?.(editor.blocksToHTMLLossy());
        });
    }, [editor]);

    const copyMarkdown = async () => {
        const markdown = editor.blocksToMarkdownLossy();

        try {
            await navigator.clipboard.writeText(markdown);
            setCopyState('copied');
        } catch {
            setCopyState('failed');
        }

        window.setTimeout(() => setCopyState('idle'), 2000);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="overflow-hidden rounded-md border border-gray-300 dark:border-gray-700">
                <div className="flex items-center justify-end gap-2 border-b border-gray-100 px-2 py-1 dark:border-gray-700">
                    <button
                        type="button"
                        onClick={() => void copyMarkdown()}
                        className={cn(
                            'rounded px-2 py-0.5 text-xs transition',
                            copyState === 'copied'
                                ? 'bg-orbit-primary/10 text-orbit-primary'
                                : copyState === 'failed'
                                  ? 'text-red-500'
                                  : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',
                        )}
                    >
                        {copyState === 'copied'
                            ? t('Copied')
                            : copyState === 'failed'
                              ? t('Copy failed')
                              : t('Copy Markdown')}
                    </button>
                </div>
                <div className="orbit-rich-text-editor" style={{ minHeight: height }}>
                    <BlockNoteView editor={editor} editable={!readOnly} theme={theme} />
                </div>
            </div>
        </FieldShell>
    );
}
