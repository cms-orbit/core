import Quill from 'quill';
import { useEffect, useRef } from 'react';
import 'quill/dist/quill.snow.css';
import type { FieldComponentProps } from '../contract';
import { FieldShell } from '../ui/field-shell';
import { attr, str } from './shared';

const TOOLBAR_GROUPS: Record<string, unknown[]> = {
    text: ['bold', 'italic', 'underline', 'strike'],
    color: [{ color: [] }, { background: [] }],
    quote: ['blockquote', 'code-block'],
    header: [{ header: [1, 2, 3, 4, false] }],
    list: [{ list: 'ordered' }, { list: 'bullet' }],
    format: [{ align: [] }, 'clean'],
    media: ['link', 'image', 'video'],
};

function buildToolbar(toolbar: unknown): unknown[] {
    if (!Array.isArray(toolbar) || toolbar.length === 0) {
        return Object.values(TOOLBAR_GROUPS);
    }

    return toolbar
        .map((group) => TOOLBAR_GROUPS[str(group)])
        .filter((group): group is unknown[] => Array.isArray(group));
}

/** Rich text editor backed by Quill 2 (replaces Orchid's Quill integration). */
export function QuillField(props: FieldComponentProps) {
    const { value, errors, onChange } = props;
    const editorRef = useRef<HTMLDivElement>(null);
    const quillRef = useRef<Quill | null>(null);
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    const toolbar = attr(props, 'toolbar');
    const height = attr<string>(props, 'height') ?? '300px';

    useEffect(() => {
        if (!editorRef.current || quillRef.current) {
            return;
        }

        const quill = new Quill(editorRef.current, {
            theme: 'snow',
            placeholder: attr<string>(props, 'placeholder'),
            readOnly: Boolean(attr(props, 'readonly') || attr(props, 'disabled')),
            modules: { toolbar: buildToolbar(toolbar) },
        });

        const initial = str(value);

        if (initial) {
            quill.clipboard.dangerouslyPasteHTML(initial);
        }

        quill.on('text-change', () => {
            onChangeRef.current?.(quill.root.innerHTML);
        });

        quillRef.current = quill;
        // Quill is initialized once; subsequent value/onChange handled via refs.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="rounded-md border border-gray-300 dark:border-gray-700">
                <div ref={editorRef} style={{ minHeight: height }} />
            </div>
        </FieldShell>
    );
}
