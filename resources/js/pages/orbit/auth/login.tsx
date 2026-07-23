import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { cn } from '../../../lib/cn';
import { useT } from '../../../lib/i18n';
import type { OrbitBrand } from '../../../theme/branding';
import { resolveBrandAsset, useBrandTheme } from '../../../theme/branding';
import { UiButton } from '../../../ui/button';
import { inputClass } from '../../../ui/field-shell';
import { ToastProvider } from '../../../ui/toast';

interface LoginPageProps {
    action: string;
    resetUrl: string;
    appName: string;
    isLockUser: boolean;
    lockUser: { name: string; identifier: string } | null;
    methods: {
        local: Array<{ value: string; label: string; inputType: string; placeholder: string }>;
        social: Array<{ value: string; label: string; url: string }>;
    };
    pendingChallenge: { identifier: string; expires_at: string } | null;
}

interface SharedProps {
    orbit?: { brand?: OrbitBrand; flash?: { message?: string | null; type?: string | null } };
    [key: string]: unknown;
}

/**
 * Wraps the login screen in the shared Orbit {@link ToastProvider} so guest
 * auth flows surface flashed `orbit.flash` messages as toasts, matching the
 * authenticated admin shell UX. Field-level validation errors stay inline.
 */
export default function Login(props: LoginPageProps) {
    return (
        <ToastProvider>
            <LoginForm {...props} />
        </ToastProvider>
    );
}

function LoginForm({
    action,
    resetUrl,
    appName,
    isLockUser,
    lockUser,
    methods,
    pendingChallenge,
}: LoginPageProps) {
    const page = usePage<SharedProps>();
    const t = useT();
    const brand = page.props.orbit?.brand;
    const brandStyle = useBrandTheme(brand);
    const brandName = brand?.name ?? appName;
    const logoUrl = resolveBrandAsset(brand, 'logo');

    const [defaultLocalMethod] = methods.local;
    const initialProvider = defaultLocalMethod?.value ?? 'email';
    const provider = methods.local.find((method) => method.value === initialProvider) ?? defaultLocalMethod;

    const form = useForm({
        provider: initialProvider,
        identifier: pendingChallenge?.identifier ?? lockUser?.identifier ?? '',
        password: '',
        challenge_code: '',
        remember: false,
    });
    const { data, setData, post, processing, errors } = form;
    const selectedMethod = methods.local.find((method) => method.value === data.provider) ?? provider;
    const isPhoneMethod = selectedMethod?.value === 'phone';
    const showPassword = !isPhoneMethod;
    const showChallenge = isPhoneMethod && pendingChallenge?.identifier === data.identifier;

    const hasLocalMethods = methods.local.length > 0;
    const hasSocialMethods = methods.social.length > 0;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(action);
    };

    return (
        <div
            style={brandStyle}
            className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12 text-gray-900 dark:bg-gray-950 dark:text-gray-100"
        >
            <Head title={`${brandName} · ${isLockUser ? t('Unlock') : t('Sign in')}`} />

            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center gap-3 text-center">
                    {logoUrl ? (
                        <img src={logoUrl} alt={brandName} className="h-10 w-auto object-contain" />
                    ) : (
                        <span className="text-xl font-semibold">{brandName}</span>
                    )}
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        {isLockUser && lockUser
                            ? t('Welcome back, :name.', { name: lockUser.name })
                            : t('Sign in to your dashboard.')}
                    </p>
                </div>

                {hasLocalMethods ? (
                    <form
                        onSubmit={submit}
                        className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        {methods.local.length > 1 ? (
                            <div className="mb-4 grid grid-cols-2 gap-2">
                                {methods.local.map((method) => (
                                    <button
                                        key={method.value}
                                        type="button"
                                        onClick={() =>
                                            setData((current) => ({
                                                ...current,
                                                provider: method.value,
                                                challenge_code: '',
                                            }))
                                        }
                                        className={cn(
                                            'rounded-lg border px-3 py-2 text-sm font-medium transition',
                                            data.provider === method.value
                                                ? 'border-orbit-primary bg-orbit-primary/10 text-orbit-primary'
                                                : 'border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-300',
                                        )}
                                    >
                                        {method.label}
                                    </button>
                                ))}
                            </div>
                        ) : null}

                        <div className="mb-4">
                            <label
                                htmlFor="identifier"
                                className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                {selectedMethod?.label ?? t('Email')}
                            </label>
                            <input
                                id="identifier"
                                type={selectedMethod?.inputType ?? 'text'}
                                name="identifier"
                                autoComplete="username"
                                autoFocus={!isLockUser}
                                readOnly={isLockUser}
                                value={data.identifier}
                                placeholder={selectedMethod?.placeholder}
                                onChange={(event) => setData('identifier', event.target.value)}
                                className={cn(inputClass, isLockUser && 'bg-gray-50 dark:bg-gray-800')}
                            />
                            {errors.identifier || errors.email ? (
                                <p className="mt-1 text-xs text-red-600">{errors.identifier ?? errors.email}</p>
                            ) : null}
                        </div>

                        {showPassword ? (
                            <div className="mb-4">
                                <label
                                    htmlFor="password"
                                    className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    {t('Password')}
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
                        ) : null}

                        {showChallenge ? (
                            <div className="mb-4">
                                <label
                                    htmlFor="challenge_code"
                                    className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200"
                                >
                                    {t('Verification code')}
                                </label>
                                <input
                                    id="challenge_code"
                                    type="text"
                                    name="challenge_code"
                                    inputMode="numeric"
                                    autoFocus
                                    value={data.challenge_code}
                                    onChange={(event) => setData('challenge_code', event.target.value)}
                                    className={inputClass}
                                />
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {t('A verification code was sent to :phone.', {
                                        phone: pendingChallenge?.identifier ?? data.identifier,
                                    })}
                                </p>
                                {errors.challenge_code ? (
                                    <p className="mt-1 text-xs text-red-600">{errors.challenge_code}</p>
                                ) : null}
                            </div>
                        ) : null}

                        {!isPhoneMethod ? (
                            <label className="mb-5 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(event) => setData('remember', event.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-orbit-primary focus:ring-orbit-primary"
                                />
                                {t('Remember Me')}
                            </label>
                        ) : null}

                        <UiButton
                            type="submit"
                            variant="primary"
                            disabled={processing}
                            className="w-full"
                        >
                            {processing
                                ? t('Signing in…')
                                : isPhoneMethod
                                  ? showChallenge
                                      ? t('Verify and sign in')
                                      : t('Send verification code')
                                  : t('Sign in')}
                        </UiButton>

                        {hasSocialMethods ? (
                            <div className="mt-5 border-t border-gray-200 pt-4 dark:border-gray-800">
                                <p className="mb-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {t('Social sign in')}
                                </p>
                                <div className="grid grid-cols-1 gap-2">
                                    {methods.social.map((method) => (
                                        <a
                                            key={method.value}
                                            href={method.url}
                                            className="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        >
                                            {method.label}
                                        </a>
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {isLockUser ? (
                            <p className="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                <Link href={resetUrl} className="text-orbit-primary hover:underline">
                                    {t('Sign in with another user.')}
                                </Link>
                            </p>
                        ) : null}
                    </form>
                ) : hasSocialMethods ? (
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p className="mb-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            {t('Sign in to your dashboard.')}
                        </p>
                        <div className="grid grid-cols-1 gap-2">
                            {methods.social.map((method) => (
                                <a
                                    key={method.value}
                                    href={method.url}
                                    className="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    {method.label}
                                </a>
                            ))}
                        </div>
                    </div>
                ) : (
                    <p className="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                        {t('No sign-in methods are configured.')}
                    </p>
                )}
            </div>
        </div>
    );
}
