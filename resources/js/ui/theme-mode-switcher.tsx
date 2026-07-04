import { useEffect, useState } from 'react';
import {
    type DarkModeSetting,
    ORBIT_THEME_MODE_EVENT,
    type ThemeModeOption,
    resolveThemeMode,
    storeThemeMode,
} from '../theme/branding';
import { cn } from '../lib/cn';
import { Icon } from './icon';

const OPTIONS: Array<{
    value: ThemeModeOption;
    label: string;
    icon: string;
}> = [
    { value: 'system', label: '시스템', icon: 'bs.display' },
    { value: 'light', label: '라이트', icon: 'bs.sun' },
    { value: 'dark', label: '다크', icon: 'bs.moon-stars' },
];

export function ThemeModeSwitcher({ defaultMode }: { defaultMode?: DarkModeSetting | null }) {
    const [mode, setMode] = useState<ThemeModeOption>(() => resolveThemeMode(defaultMode));

    useEffect(() => {
        setMode(resolveThemeMode(defaultMode));
    }, [defaultMode]);

    useEffect(() => {
        const sync = () => setMode(resolveThemeMode(defaultMode));

        window.addEventListener(ORBIT_THEME_MODE_EVENT, sync);

        return () => window.removeEventListener(ORBIT_THEME_MODE_EVENT, sync);
    }, [defaultMode]);

    return (
        <div className="hidden items-center rounded-full border border-gray-200 bg-gray-50 p-1 sm:flex dark:border-white/10 dark:bg-white/5">
            {OPTIONS.map((option) => {
                const active = mode === option.value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        onClick={() => storeThemeMode(option.value)}
                        className={cn(
                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-xs font-medium transition',
                            active
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-900 dark:text-gray-100'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                        )}
                        aria-pressed={active}
                    >
                        <Icon name={option.icon} className="text-sm" />
                        <span>{option.label}</span>
                    </button>
                );
            })}
        </div>
    );
}
