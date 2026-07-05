<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Support;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use Illuminate\Support\Str;

class LoginIdentifierNormalizer
{
    public static function normalize(LoginProvider|string $provider, ?string $identifier): ?string
    {
        if ($identifier === null) {
            return null;
        }

        $trimmed = trim($identifier);

        if ($trimmed === '') {
            return null;
        }

        $provider = is_string($provider) ? LoginProvider::from($provider) : $provider;

        return match ($provider) {
            LoginProvider::Email => Str::lower($trimmed),
            LoginProvider::Phone => preg_replace('/\D+/', '', $trimmed) ?: null,
            LoginProvider::Id    => Str::lower($trimmed),
            default              => $trimmed,
        };
    }
}
