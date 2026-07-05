<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands\Support;

enum InstallLocale: string
{
    case Korean = 'ko';
    case English = 'en';

    public static function defaultFromAppLocale(?string $locale = null): self
    {
        $locale ??= (string) config('app.locale', 'en');

        return str_starts_with(strtolower($locale), 'ko')
            ? self::Korean
            : self::English;
    }

    public function label(): string
    {
        return match ($this) {
            self::Korean  => '한국어 (Korean)',
            self::English => 'English',
        };
    }
}
