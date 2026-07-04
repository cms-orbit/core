import { useEffect, useState, type ComponentType } from 'react';
import type { FieldComponentProps } from '../contract';
import { MapLatLngInputs, readLatLng } from './map-utils';

/**
 * SSR-safe map field wrapper. Leaflet is loaded only in the browser so
 * Inertia SSR does not evaluate window-dependent modules.
 */
export function MapField(props: FieldComponentProps) {
    const { value, onChange } = props;
    const [ClientField, setClientField] = useState<ComponentType<FieldComponentProps> | null>(null);

    useEffect(() => {
        void import('./map.client').then((module) => {
            setClientField(() => module.MapFieldClient);
        });
    }, []);

    if (ClientField) {
        return <ClientField {...props} />;
    }

    return <MapLatLngInputs props={props} position={readLatLng(value)} onChange={onChange} />;
}
