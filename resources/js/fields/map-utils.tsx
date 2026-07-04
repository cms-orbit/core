import type { FieldComponentProps } from '../contract';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr } from './shared';

export interface LatLng {
    lat: number;
    lng: number;
}

export function readLatLng(value: unknown): LatLng | null {
    if (value && typeof value === 'object') {
        const candidate = value as Record<string, unknown>;
        const lat = Number(candidate.lat);
        const lng = Number(candidate.lng);

        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
            return { lat, lng };
        }
    }

    return null;
}

export function MapLatLngInputs({
    props,
    position,
    onChange,
}: {
    props: FieldComponentProps;
    position: LatLng | null;
    onChange?: (value: LatLng) => void;
}) {
    const update = (next: Partial<LatLng>) => {
        const merged = { lat: position?.lat ?? 0, lng: position?.lng ?? 0, ...next };
        onChange?.(merged);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={props.errors[0]}
        >
            <div
                className="mb-2 flex items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400"
                style={{ height: attr<string>(props, 'height') ?? '300px' }}
            >
                Map preview loads in the browser.
            </div>
            <div className="flex gap-2">
                <input
                    type="number"
                    step="any"
                    placeholder="Latitude"
                    className={inputClass}
                    value={position?.lat ?? ''}
                    onChange={(event) => update({ lat: Number(event.target.value) })}
                />
                <input
                    type="number"
                    step="any"
                    placeholder="Longitude"
                    className={inputClass}
                    value={position?.lng ?? ''}
                    onChange={(event) => update({ lng: Number(event.target.value) })}
                />
            </div>
        </FieldShell>
    );
}
