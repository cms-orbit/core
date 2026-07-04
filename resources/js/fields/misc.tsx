import { useState } from 'react';
import type { FieldComponentProps, FieldNode } from '../contract';
import { FieldRenderer } from '../screen-renderer';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr, str } from './shared';

const UTM_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

/** URL builder that appends UTM query parameters to a base URL. */
export function UtmField(props: FieldComponentProps) {
    const { name, value, errors, onChange } = props;
    const [expanded, setExpanded] = useState(false);

    const updateParam = (param: string, paramValue: string) => {
        try {
            const url = new URL(str(value) || 'https://example.com');

            if (paramValue) {
                url.searchParams.set(param, paramValue);
            } else {
                url.searchParams.delete(param);
            }

            onChange?.(url.toString());
        } catch {
            onChange?.(str(value));
        }
    };

    const readParam = (param: string): string => {
        try {
            return new URL(str(value)).searchParams.get(param) ?? '';
        } catch {
            return '';
        }
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <input
                type="url"
                name={name ?? undefined}
                className={inputClass}
                placeholder={attr<string>(props, 'placeholder') ?? 'https://…'}
                value={str(value)}
                onChange={(event) => onChange?.(event.target.value)}
            />
            <button
                type="button"
                onClick={() => setExpanded((current) => !current)}
                className="mt-1 text-xs text-orbit-primary hover:underline"
            >
                {expanded ? 'UTM 파라미터 숨기기' : 'UTM 파라미터 편집'}
            </button>
            {expanded ? (
                <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    {UTM_PARAMS.map((param) => (
                        <label key={param} className="text-xs text-gray-500">
                            {param}
                            <input
                                className={inputClass}
                                value={readParam(param)}
                                onChange={(event) => updateParam(param, event.target.value)}
                            />
                        </label>
                    ))}
                </div>
            ) : null}
        </FieldShell>
    );
}

/** Horizontal group of fields. Usually intercepted by FieldRenderer's group path. */
export function GroupField(props: FieldComponentProps) {
    const nested = (props.node.fields ?? (attr(props, 'group') as FieldNode[] | undefined)) ?? [];

    if (nested.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-end gap-3">
            {nested.map((child, index) => (
                <div key={child.name ?? `${child.component}-${index}`} className="min-w-0 flex-1">
                    <FieldRenderer node={child} data={props.data} screen={props.screen} />
                </div>
            ))}
        </div>
    );
}

/**
 * View field escape hatch. Renders pre-rendered HTML when supplied; otherwise
 * prefer a custom ReactField component for arbitrary markup.
 */
export function ViewFieldField(props: FieldComponentProps) {
    const html = attr<string>(props, 'html') ?? attr<string>(props, 'rendered');

    return (
        <FieldShell title={attr<string>(props, 'title')} help={attr<string>(props, 'help')}>
            {html ? (
                <div dangerouslySetInnerHTML={{ __html: html }} />
            ) : (
                <div className="text-sm text-gray-700 dark:text-gray-200">{str(props.value)}</div>
            )}
        </FieldShell>
    );
}
