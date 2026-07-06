<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics\Support;

use Illuminate\Http\Request;

/**
 * Resolves which database connection the analytics tracker should read from and
 * write to for a given request.
 *
 * Core has no knowledge of multi-instance hosting, so it defaults to the
 * application's default connection. Optional packages (e.g. cms-orbit/saas) may
 * register a resolver during boot to redirect host-application requests onto a
 * dedicated connection, without Core needing to depend on those packages.
 */
final class AnalyticsSchemaConnection
{
    /**
     * @var (callable(Request): ?string)|null
     */
    private static $resolver = null;

    /**
     * Register a resolver invoked with the current request. Returning a
     * non-empty string selects that connection; returning null (or an empty
     * string) falls back to the application's default connection.
     *
     * @param  (callable(Request): ?string)|null  $resolver
     */
    public static function resolveUsing(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * Remove any registered resolver and fall back to the default connection.
     */
    public static function forget(): void
    {
        self::$resolver = null;
    }

    public static function for(?Request $request): string
    {
        if ($request !== null && self::$resolver !== null) {
            $resolved = (self::$resolver)($request);

            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }
        }

        return (string) config('database.default');
    }
}
