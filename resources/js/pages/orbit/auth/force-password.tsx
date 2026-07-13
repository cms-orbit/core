import { Head, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useT } from '../../../lib/i18n';
import type { OrbitBrand } from '../../../theme/branding';
import { resolveBrandAsset, useBrandTheme } from '../../../theme/branding';
import { UiButton } from '../../../ui/button';
import { inputClass } from '../../../ui/field-shell';

interface ForcePasswordPageProps {
    appName: string;
    email: string;
    action: string;
    logoutUrl: string;
}

interface SharedProps {
    orbit?: { brand?: OrbitBrand };
    [key: string]: unknown;
}

export default function ForcePassword({ appName, email, action, logoutUrl }: ForcePasswordPageProps) {
    const page = usePage<SharedProps>();
    const t = useT();
    const brand = page.props.orbit?.brand;
    const brandStyle = useBrandTheme(brand);
    const brandName = brand?.name ?? appName;
    const logoUrl = resolveBrandAsset(brand, 'logo');
    const form = useForm({
        password: '',
        password_confirmation: '',
    });
    const { data, setData, put, processing, errors } = form;

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        put(action, {
            onSuccess: () => form.resetAndClearErrors('password', 'password_confirmation'),
        });
    };

    return (
        <div
            style={brandStyle}
            className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
        >
            <Head title={`${brandName} · ${t('Change your password')}`} />

            <div className="w-full max-w-md">
                <div className="mb-6 flex flex-col items-center gap-3 text-center">
                    {logoUrl ? (
                        <img src={logoUrl} alt={brandName} className="h-10 w-auto object-contain" />
                    ) : (
                        <span className="text-xl font-semibold">{brandName}</span>
                    )}
                    <div className="space-y-2">
                        <p className="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {t('Change your password to continue')}
                        </p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {t(
                                'This account must set a new password before it can continue anywhere else in Orbit.',
                            )}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            {t('Signed in as')} <span className="font-medium">{email}</span>
                        </p>
                    </div>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div className="mb-4">
                        <label
                            htmlFor="password"
                            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                        >
                            {t('New password')}
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autoComplete="new-password"
                            autoFocus
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            className={inputClass}
                        />
                        {errors.password ? <p className="mt-1 text-xs text-red-600">{errors.password}</p> : null}
                    </div>

                    <div className="mb-6">
                        <label
                            htmlFor="password_confirmation"
                            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                        >
                            {t('Confirm new password')}
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            className={inputClass}
                        />
                        {errors.password_confirmation ? (
                            <p className="mt-1 text-xs text-red-600">{errors.password_confirmation}</p>
                        ) : null}
                    </div>

                    <div className="flex flex-col gap-3">
                        <UiButton type="submit" variant="primary" disabled={processing} className="w-full">
                            {processing ? t('Updating password…') : t('Update password')}
                        </UiButton>

                        <UiButton
                            type="button"
                            variant="ghost"
                            disabled={processing}
                            className="w-full"
                            onClick={() => router.post(logoutUrl)}
                        >
                            {t('Log out')}
                        </UiButton>
                    </div>
                </form>
            </div>
        </div>
    );
}
