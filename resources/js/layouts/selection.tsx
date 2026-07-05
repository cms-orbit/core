import { router } from '@inertiajs/react';
import type { FieldNode, LayoutComponentProps } from '../contract';
import { FormProvider, useOrbitForm } from '../form-context';
import { appendFilterFieldToParams, visitTableGet } from './entity-table-toolbar';
import { FieldRenderer } from '../screen-renderer';
import { UiButton } from '../ui/button';
import { Card, CardBody, CardHeader } from '../ui/card';

function asFields(value: unknown): FieldNode[] {
    return Array.isArray(value) ? (value as FieldNode[]) : [];
}

/**
 * Filter selection panel. Renders filter fields (form='filters') in their own
 * form scope and submits them as query parameters via an Inertia GET visit.
 */
export function SelectionLayout({ node, data, screen }: LayoutComponentProps) {
    const fields = asFields(node.data.fields);

    if (fields.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader title={(node.data.title as string | null) ?? 'Filters'} />
            <CardBody>
                <FormProvider initialData={{}} state={null}>
                    <SelectionForm fields={fields} data={data} screen={screen} />
                </FormProvider>
            </CardBody>
        </Card>
    );
}

function SelectionForm({
    fields,
    data,
    screen,
}: {
    fields: FieldNode[];
    data: Record<string, unknown>;
    screen: LayoutComponentProps['screen'];
}) {
    const form = useOrbitForm();

    const apply = () => {
        visitTableGet((params) => {
            params.delete('page');

            for (const field of fields) {
                if (!field.name) {
                    continue;
                }

                appendFilterFieldToParams(params, field.name, form.getValue(field.name));
            }
        });
    };

    const reset = () => {
        router.get(window.location.pathname, {}, { preserveScroll: true, replace: true });
    };

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                {fields.map((field, index) => (
                    <FieldRenderer
                        key={field.name ?? `${field.component}-${index}`}
                        node={field}
                        data={data}
                        screen={screen}
                    />
                ))}
            </div>
            <div className="flex gap-2">
                <UiButton type="button" variant="primary" onClick={apply}>
                    Apply
                </UiButton>
                <UiButton type="button" variant="default" onClick={reset}>
                    Reset
                </UiButton>
            </div>
        </div>
    );
}
