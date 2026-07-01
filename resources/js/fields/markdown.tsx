import { marked } from 'marked';
import { useMemo, useRef, useState } from 'react';
import type { FieldComponentProps } from '../contract';
import { cn } from '../lib/cn';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr, str } from './shared';

interface ToolbarButton {
    label: string;
    apply: (selected: string) => { text: string; cursor?: number };
}

const TOOLBAR: ToolbarButton[] = [
    { label: 'H2', apply: (s) => ({ text: `## ${s}` }) },
    { label: 'H3', apply: (s) => ({ text: `### ${s}` }) },
    { label: 'B', apply: (s) => ({ text: `**${s || 'bold'}**` }) },
    { label: 'I', apply: (s) => ({ text: `_${s || 'italic'}_` }) },
    { label: 'Link', apply: (s) => ({ text: `[${s || 'text'}](https://)` }) },
    { label: 'Quote', apply: (s) => ({ text: `> ${s}` }) },
    { label: 'Code', apply: (s) => ({ text: '```\n' + s + '\n```' }) },
    { label: 'List', apply: (s) => ({ text: `- ${s}` }) },
];

/** Markdown editor with live preview (replaces SimpleMDE). */
export function MarkdownField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const [tab, setTab] = useState<'write' | 'preview'>('write');
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const rows = Number(attr(props, 'rows') ?? 8);

    const text = str(value);
    const preview = useMemo(() => marked.parse(text, { async: false }) as string, [text]);

    const applyButton = (button: ToolbarButton) => {
        const textarea = textareaRef.current;

        if (!textarea) {
            return;
        }

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selected = text.slice(start, end);
        const { text: replacement } = button.apply(selected);
        const next = text.slice(0, start) + replacement + text.slice(end);
        onChange?.(next);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="rounded-md border border-gray-300 dark:border-gray-700">
                <div className="flex items-center gap-1 border-b border-gray-100 px-2 py-1 dark:border-gray-700">
                    {TOOLBAR.map((button) => (
                        <button
                            key={button.label}
                            type="button"
                            onClick={() => applyButton(button)}
                            className="rounded px-1.5 py-0.5 text-xs text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            {button.label}
                        </button>
                    ))}
                    <div className="ml-auto flex gap-1">
                        {(['write', 'preview'] as const).map((mode) => (
                            <button
                                key={mode}
                                type="button"
                                onClick={() => setTab(mode)}
                                className={cn(
                                    'rounded px-2 py-0.5 text-xs capitalize',
                                    tab === mode ? 'bg-orbit-primary/10 text-orbit-primary' : 'text-gray-500',
                                )}
                            >
                                {mode}
                            </button>
                        ))}
                    </div>
                </div>
                {tab === 'write' ? (
                    <textarea
                        ref={textareaRef}
                        name={name ?? undefined}
                        rows={rows}
                        className={cn(inputClass, 'rounded-none border-0 focus:ring-0')}
                        value={text}
                        onChange={(event) => onChange?.(event.target.value)}
                    />
                ) : (
                    <div
                        className="prose prose-sm max-w-none p-3 dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: preview }}
                    />
                )}
            </div>
        </FieldShell>
    );
}
