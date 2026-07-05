import { useEffect, useState } from 'react';
import {
    type DarkModeSetting,
    ORBIT_THEME_MODE_EVENT,
    type ThemeModeOption,
    normalizeThemeMode,
    resolveThemeMode,
    storeThemeMode,
} from '../theme/branding';
import { cn } from '../lib/cn';
import { useT } from '../lib/i18n';
import { Icon } from './icon';

const OPTIONS: Array<{
    value: ThemeModeOption;
    label: string;
    icon: string;
}> = [
    { value: 'system', label: 'System', icon: 'bs.circle-half' },
    { value: 'light', label: 'Light', icon: 'bs.sun' },
    { value: 'dark', label: 'Dark', icon: 'bs.moon' },
];

export function ThemeModeSwitcher({ defaultMode }: { defaultMode?: DarkModeSetting | null }) {
    const t = useT();
    const [open, setOpen] = useState(false);
    const [mode, setMode] = useState<ThemeModeOption>(() => normalizeThemeMode(defaultMode));

    useEffect(() => {
        setMode(resolveThemeMode(defaultMode));
    }, [defaultMode]);

    useEffect(() => {
        const sync = () => setMode(resolveThemeMode(defaultMode));

        window.addEventListener(ORBIT_THEME_MODE_EVENT, sync);

        return () => window.removeEventListener(ORBIT_THEME_MODE_EVENT, sync);
    }, [defaultMode]);

    const current = OPTIONS.find((option) => option.value === mode) ?? OPTIONS[0];

    const choose = (value: ThemeModeOption) => {
        setOpen(false);
        storeThemeMode(value);
    };

    return (
        <div className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                aria-label={t('Theme')}
                aria-expanded={open}
            >
                <Icon name={current.icon} className="text-base" />
                <span className="hidden sm:inline">{t(current.label)}</span>
                <Icon name="bs.chevron-down" className={cn('text-xs transition', open && 'rotate-180')} />
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
                    <div className="absolute right-0 z-40 mt-2 w-44 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        {OPTIONS.map((option) => {
                            const active = mode === option.value;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => choose(option.value)}
                                    className={cn(
                                        'flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50',
                                        active
                                            ? 'font-medium text-orbit-primary'
                                            : 'text-gray-700 dark:text-gray-200',
                                    )}
                                    aria-pressed={active}
                                >
                                    <span className="flex items-center gap-2">
                                        <Icon name={option.icon} className="text-sm" />
                                        <span>{t(option.label)}</span>
                                    </span>
                                    {active ? <Icon name="bs.check2" className="text-sm" /> : null}
                                </button>
                            );
                        })}
                    </div>
                </>
            ) : null}
        </div>
    );
}
