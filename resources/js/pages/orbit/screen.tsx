import { Head } from '@inertiajs/react';
import {
    ActionBar,
    DashboardLayout,
    FormProvider,
    MissingComponent,
    resolveComponent,
    ScreenRenderer,
} from '../../index';
import type {
    CustomComponentProps,
    ScreenContext,
    ScreenProps,
} from '../../index';
import type { CustomComponent } from '../../registry';

export default function Screen(props: ScreenProps) {
    const {
        name,
        description,
        shell,
        breadcrumbs,
        commandBar,
        layout,
        data,
        state,
        screenComponent,
    } = props;

    const screen: ScreenContext = {
        name,
        description,
        breadcrumbs,
        data,
        state,
    };

    const body = screenComponent ? (
        <CustomScreen name={screenComponent} data={data} screen={screen} />
    ) : (
        <ScreenRenderer layout={layout} data={data} screen={screen} />
    );

    return (
        <FormProvider initialData={data} state={state}>
            <Head title={name ?? 'Orbit'} />
            <DashboardLayout
                title={name}
                description={description}
                chrome={shell?.chrome}
                contentWidth={shell?.contentWidth}
                breadcrumbs={breadcrumbs}
                actions={<ActionBar actions={commandBar} data={data} screen={screen} />}
            >
                {body}
            </DashboardLayout>
        </FormProvider>
    );
}

function CustomScreen({
    name,
    data,
    screen,
}: {
    name: string;
    data: Record<string, unknown>;
    screen: ScreenContext;
}) {
    const Component = resolveComponent(name) as CustomComponent | undefined;

    if (!Component) {
        return <MissingComponent kind="screen" name={name} />;
    }

    const componentProps: CustomComponentProps = { data, screen };

    return <Component {...componentProps} />;
}
