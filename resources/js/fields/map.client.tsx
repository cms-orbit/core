import 'leaflet/dist/leaflet.css';
import { CircleMarker, MapContainer, TileLayer, useMapEvents } from 'react-leaflet';
import type { FieldComponentProps } from '../contract';
import { FieldShell, inputClass } from '../ui/field-shell';
import { attr } from './shared';
import { type LatLng, readLatLng } from './map-utils';

function ClickCapture({ onPick }: { onPick: (latLng: LatLng) => void }) {
    useMapEvents({
        click(event) {
            onPick({ lat: event.latlng.lat, lng: event.latlng.lng });
        },
    });

    return null;
}

/** Browser-only map picker (react-leaflet). */
export function MapFieldClient(props: FieldComponentProps) {
    const { value, errors, onChange } = props;
    const zoom = Number(attr(props, 'zoom') ?? 14);
    const height = attr<string>(props, 'height') ?? '300px';
    const position = readLatLng(value);
    const center: [number, number] = position ? [position.lat, position.lng] : [40.7128, -74.006];

    const update = (next: Partial<LatLng>) => {
        const merged = { lat: position?.lat ?? 0, lng: position?.lng ?? 0, ...next };
        onChange?.(merged);
    };

    return (
        <FieldShell
            title={attr<string>(props, 'title')}
            help={attr<string>(props, 'help')}
            required={attr<boolean>(props, 'required')}
            error={errors[0]}
        >
            <div className="overflow-hidden rounded-md border border-gray-300 dark:border-gray-700" style={{ height }}>
                <MapContainer center={center} zoom={zoom} style={{ height: '100%', width: '100%' }}>
                    <TileLayer
                        attribution='&copy; OpenStreetMap contributors'
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    />
                    <ClickCapture onPick={(latLng) => onChange?.(latLng)} />
                    {position ? <CircleMarker center={[position.lat, position.lng]} radius={8} /> : null}
                </MapContainer>
            </div>
            <div className="mt-2 flex gap-2">
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
