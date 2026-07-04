import { router } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '../lib/cn';
import { useI18n, useT } from '../lib/i18n';
import { Icon } from './icon';

/**
 * Header language switcher. Reads the shared `orbit.i18n` payload and POSTs the
 * chosen locale to `orbit.locale.switch`, which stores it on the session (and
 * the authenticated user) before reloading the current page.
 */
export function LanguageSwitcher() {
    const t = useT();
    const { locale, available, switchUrl } = useI18n();
    const [open, setOpen] = useState(false);

    if (!switchUrl || available.length <= 1) {
        return null;
    }

    const current = available.find((option) => option.code === locale);

    const choose = (code: string) => {
        setOpen(false);

        if (code === locale) {
            return;
        }

        router.post(switchUrl, { locale: code }, { preserveScroll: true });
    };

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm"
                style={{ color: 'var(--color-orbit-nav-group-fg, #64748b)' }}
                aria-label={t('Language')}
            >
                <Icon name="bs.translate" className="text-base" />
                <span className="hidden sm:inline">{current?.label ?? locale.toUpperCase()}</span>
                <Icon name="bs.chevron-down" className="text-xs" />
            </button>

            {open ? (
                <>
                    <button
                        type="button"
                        aria-hidden="true"
                        tabIndex={-1}
                        className="fixed inset-0 z-30 cursor-default"
                        onClick={() => setOpen(false)}
                    />
                    <div
                        className="absolute right-0 z-40 mt-2 w-44 overflow-hidden rounded-lg border py-1 shadow-lg"
                        style={{
                            backgroundColor: 'var(--color-orbit-panel-bg, #ffffff)',
                            borderColor: 'var(--color-orbit-panel-border, #e2e8f0)',
                        }}
                    >
                        {available.map((option) => (
                            <button
                                key={option.code}
                                type="button"
                                onClick={() => choose(option.code)}
                                className={cn(
                                    'flex w-full items-center justify-between px-3 py-2 text-left text-sm',
                                    option.code === locale
                                        ? 'font-medium text-orbit-primary'
                                        : '',
                                )}
                                style={option.code === locale ? undefined : { color: 'var(--color-orbit-secondary, #334155)' }}
                            >
                                <span>{option.label}</span>
                                {option.code === locale ? (
                                    <Icon name="bs.check2" className="text-sm" />
                                ) : null}
                            </button>
                        ))}
                    </div>
                </>
            ) : null}
        </div>
    );
}
