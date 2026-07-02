<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

class Locale
{
    /**
     * Native display names for the locales Orbit ships labels for. Any locale
     * not listed here falls back to its uppercased code in {@see label()}.
     *
     * @var array<string, string>
     */
    private const NAMES = [
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
        'zh' => '中文',
        'zh_TW' => '繁體中文',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'pt' => 'Português',
        'ru' => 'Русский',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'tr' => 'Türkçe',
        'vi' => 'Tiếng Việt',
        'id' => 'Bahasa Indonesia',
        'th' => 'ไทย',
        'ar' => 'العربية',
        'fa' => 'فارسی',
        'he' => 'עברית',
    ];

    /**
     * Extra JSON translation directories contributed by satellite packages.
     * Merged into {@see messages()} so package-owned translations reach the
     * React client-side translator (which consumes the shared i18n prop).
     *
     * @var array<int, string>
     */
    private static array $paths = [];

    /**
     * Register a directory holding "{locale}.json" translation files so its
     * messages are exposed to the admin client. Typically called from a
     * package service provider with its resources/lang path.
     */
    public static function registerPath(string $directory): void
    {
        $directory = rtrim($directory, '/');

        if ($directory !== '' && ! in_array($directory, self::$paths, true)) {
            self::$paths[] = $directory;
        }
    }

    /**
     * Language codes with RTL writing direction.
     *
     * Source: https://meta.wikimedia.org/wiki/Template:List_of_language_names_ordered_by_code
     */
    private const RTL = [
        'ar', // Arabic
        'arc', // Aramaic
        'ckb', // Central Kurdish
        'dv', // Divehi
        'fa', // Persian
        'ha', // Hausa
        'he', // Hebrew
        'khw', // Khowar
        'ks', // Kashmiri
        'ps', // Pashto
        'sd', // Sindhi
        'ur', // Urdu
        'uz_AF', // Uzbeki Afghanistan
        'yi', // Yiddish
    ];

    /**
     * Get the directionality of the current language, based on its writing direction.
     */
    public static function currentDir(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return in_array($locale, self::RTL) ? 'rtl' : 'ltr';
    }

    /**
     * Check if the current or given locale has RTL direction.
     */
    public static function isRtl(?string $locale = null): bool
    {
        $locale ??= app()->getLocale();

        return self::currentDir($locale) === 'rtl';
    }

    /**
     * The default admin interface locale (stored setting → config → 'ko').
     */
    public static function default(): string
    {
        return (string) orbit_config('locale.default', config('orbit.locale.default', 'ko'));
    }

    /**
     * The fallback locale used when a translation key is missing.
     */
    public static function fallback(): string
    {
        return (string) orbit_config('locale.fallback', config('orbit.locale.fallback', 'en'));
    }

    /**
     * Locales offered in the admin language switcher.
     *
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return self::normalize(orbit_config('locale.supported', config('orbit.locale.supported', ['ko', 'en'])));
    }

    /**
     * Locales used for translatable content fields.
     *
     * @return array<int, string>
     */
    public static function content(): array
    {
        return self::normalize(orbit_config('locale.content', config('orbit.locale.content', ['ko', 'en'])));
    }

    /**
     * Human-readable native label for a locale code.
     */
    public static function label(string $locale): string
    {
        return self::NAMES[$locale] ?? strtoupper($locale);
    }

    /**
     * Supported locales as a code => label map for select fields / switchers.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::supported() as $code) {
            $options[$code] = self::label($code);
        }

        return $options;
    }

    /**
     * All locales Orbit ships display names for, as a code => label map.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::NAMES;
    }

    /**
     * The JSON translation messages for a locale, merged so host overrides win.
     *
     * Sources (later wins): core resources/lang, satellite package lang dirs
     * registered via {@see registerPath()}, application lang path, published
     * vendor override (lang/vendor/orbit). Consumed by the React client-side
     * translator via the shared `orbit.i18n` prop.
     *
     * @return array<string, string>
     */
    public static function messages(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $files = [
            __DIR__.'/../../resources/lang/'.$locale.'.json',
        ];

        foreach (self::$paths as $directory) {
            $files[] = $directory.'/'.$locale.'.json';
        }

        $files[] = function_exists('lang_path') ? lang_path($locale.'.json') : null;
        $files[] = function_exists('lang_path') ? lang_path('vendor/orbit/'.$locale.'.json') : null;

        $messages = [];

        foreach (array_filter($files) as $file) {
            if (! is_string($file) || ! is_file($file)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($file), true);

            if (is_array($decoded)) {
                $messages = array_merge($messages, $decoded);
            }
        }

        return $messages;
    }

    /**
     * Coerce a stored value (array or comma string) into a clean list of codes.
     *
     * @return array<int, string>
     */
    private static function normalize(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return collect((array) $value)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
