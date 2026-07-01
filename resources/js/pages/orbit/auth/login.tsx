import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { cn } from '../../../lib/cn';
import type { OrbitBrand } from '../../../theme/branding';
import { useBrandTheme } from '../../../theme/branding';
import { UiButton } from '../../../ui/button';
import { inputClass } from '../../../ui/field-shell';

interface LoginPageProps {
    /** URL the credentials are POSTed to (orbit.login.auth). */
    action: string;
    /** URL that clears the "remembered" lock user (orbit.login.lock). */
    resetUrl: string;
    appName: string;
    isLockUser: boolean;
    lockUser: { name: string; email: string } | null;
}

interface SharedProps {
    orbit?: { brand?: OrbitBrand };
    [key: string]: unknown;
}

export default function Login({
    action,
    resetUrl,
    appName,
    isLockUser,
    lockUser,
}: LoginPageProps) {
    const page = usePage<SharedProps>();
    const brand = page.props.orbit?.brand;
    const brandStyle = useBrandTheme(brand);
    const brandName = brand?.name ?? appName;

    const form = useForm({
        email: lockUser?.email ?? '',
        password: '',
        remember: false,
    });
    const { data, setData, post, processing, errors } = form;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(action);
    };

    return (
        <div
            style={brandStyle}
            className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
        >
            <Head title={`${brandName} · ${isLockUser ? 'Unlock' : 'Sign in'}`} />

            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center gap-3 text-center">
                    {brand?.logo ? (
                        <img src={brand.logo} alt={brandName} className="h-10 w-auto" />
                    ) : (
                        <span className="text-xl font-semibold">{brandName}</span>
                    )}
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        {isLockUser && lockUser
                            ? `Welcome back, ${lockUser.name}.`
                            : 'Sign in to your dashboard.'}
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <div className="mb-4">
                        <label
                            htmlFor="email"
                            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                        >
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="username"
                            autoFocus={!isLockUser}
                            readOnly={isLockUser}
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            className={cn(inputClass, isLockUser && 'bg-gray-50 dark:bg-gray-800')}
                        />
                        {errors.email ? (
                            <p className="mt-1 text-xs text-red-600">{errors.email}</p>
                        ) : null}
                    </div>

                    <div className="mb-4">
                        <label
                            htmlFor="password"
                            className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autoComplete="current-password"
                            autoFocus={isLockUser}
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            className={inputClass}
                        />
                        {errors.password ? (
                            <p className="mt-1 text-xs text-red-600">{errors.password}</p>
                        ) : null}
                    </div>

                    <label className="mb-5 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                            className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                        />
                        Remember me
                    </label>

                    <UiButton
                        type="submit"
                        variant="primary"
                        disabled={processing}
                        className="w-full"
                    >
                        {processing ? 'Signing in…' : 'Sign in'}
                    </UiButton>

                    {isLockUser ? (
                        <p className="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            <Link href={resetUrl} className="text-orbit-primary hover:underline">
                                Sign in as a different user
                            </Link>
                        </p>
                    ) : null}
                </form>
            </div>
        </div>
    );
}
