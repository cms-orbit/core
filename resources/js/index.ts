/**
 * Orbit React foundation entry point.
 *
 * Importing this module registers all built-in layout/field/action components
 * into the registries and re-exports the public API (including the
 * `registerComponents` escape hatch) for host apps and packages.
 */
import { actionComponents } from './actions';
import { ColorPickerField } from './fields/custom';
import { fieldComponents } from './fields';
import { layoutComponents } from './layouts';
import { registerActions, registerComponents, registerFields, registerLayouts } from './registry';

let registered = false;

/** Register all built-in Orbit components. Idempotent. */
export function registerOrbitComponents(): void {
    if (registered) {
        return;
    }

    registered = true;

    registerLayouts(layoutComponents);
    registerFields(fieldComponents);
    registerActions(actionComponents);
    registerComponents({
        ColorPicker: ColorPickerField,
    });
}

registerOrbitComponents();

export * from './contract';
export * from './registry';
export { FormProvider, useOrbitForm, useOptionalOrbitForm, nameToPath } from './form-context';
export {
    ScreenRenderer,
    LayoutNodeRenderer,
    LayoutChildren,
    FieldRenderer,
    ActionRenderer,
    ActionBar,
} from './screen-renderer';
export { DashboardLayout } from './shell/dashboard-layout';
export type { OrbitMenuItem, SharedOrbit } from './shell/dashboard-layout';
export { cn } from './lib/cn';
export { orbitFetch, readCookie } from './lib/http';

// Shared UI primitives
export { UiButton } from './ui/button';
export { Card, CardBody, CardHeader } from './ui/card';
export { FieldShell, inputClass } from './ui/field-shell';
export { Overlay } from './ui/overlay';
export type { OverlaySize, OverlayPlacement } from './ui/overlay';
export { OrbitProviders } from './ui/providers';
export { ToastProvider, useToast, useOptionalToast } from './ui/toast';
export type { ToastType, ToastOptions } from './ui/toast';
export { ConfirmProvider, useConfirm } from './ui/confirm';
export type { ConfirmOptions } from './ui/confirm';
export { ModalProvider, useModal, useOptionalModal, useAsyncLayout } from './ui/modal';
export type { ModalOpenConfig, ModalState } from './ui/modal';
export { Banner } from './ui/banner';
export type { BannerTone } from './ui/banner';
export { NotificationCenter } from './ui/notification';
export type { OrbitNotification } from './ui/notification';

// Branding theme
export { useBrandTheme, resolveBrandColors, PALETTE_PRESETS } from './theme/branding';
export type { OrbitBrand, BrandColors, DarkModeSetting } from './theme/branding';

// Media library
export { MediaLibraryDialog } from './media/library';
export type { MediaLibraryDialogProps } from './media/library';
export { useMediaEndpoints } from './media/use-media-endpoints';
export { listMedia, uploadMedia, deleteMedia } from './media/api';
export { DEFAULT_MEDIA_ENDPOINTS, inferMediaType, formatBytes } from './media/types';
export type {
    MediaItem,
    MediaType,
    MediaEndpoints,
    MediaListResponse,
    MediaQuery,
    EncodingStatus,
} from './media/types';
