<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LoginMethodRegistry
{
    /**
     * @return Collection<int, LoginProvider>
     */
    public function enabledLocalProviders(): Collection
    {
        return collect(LoginProvider::local())
            ->filter(fn (LoginProvider $provider): bool => $this->isEnabled($provider))
            ->values();
    }

    /**
     * @return Collection<int, LoginProvider>
     */
    public function enabledSocialProviders(): Collection
    {
        return collect(LoginProvider::social())
            ->filter(fn (LoginProvider $provider): bool => $this->isEnabled($provider))
            ->values();
    }

    public function isEnabled(LoginProvider|string $provider): bool
    {
        $provider = is_string($provider) ? LoginProvider::from($provider) : $provider;

        $default = match ($provider) {
            LoginProvider::Email => true,
            default              => false,
        };

        return (bool) orbit_config("auth_methods.{$provider->value}.enabled", $default);
    }

    public function requiresEmailVerification(): bool
    {
        return (bool) orbit_config('auth_methods.email.require_verification', false);
    }

    public function phoneVerificationChannel(): string
    {
        $channel = (string) orbit_config('auth_methods.phone.verification_channel', 'sms');

        return in_array($channel, ['sms', 'alimtalk'], true) ? $channel : 'sms';
    }

    public function phoneChallengeTtlSeconds(): int
    {
        return max((int) orbit_config('auth_methods.phone.challenge_ttl_seconds', 300), 60);
    }

    public function idMinLength(): int
    {
        return max((int) orbit_config('auth_methods.id.min_length', 4), 1);
    }

    public function idMaxLength(): int
    {
        return max((int) orbit_config('auth_methods.id.max_length', 24), $this->idMinLength());
    }

    /**
     * @return array<int, string>
     */
    public function blockedIds(): array
    {
        $raw = orbit_config('auth_methods.id.blocked_values', 'admin,manager,ceo');

        if (is_array($raw)) {
            return collect($raw)
                ->map(fn ($value): string => Str::lower(trim((string) $value)))
                ->filter()
                ->values()
                ->all();
        }

        return collect(explode(',', (string) $raw))
            ->map(fn ($value): string => Str::lower(trim($value)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function providerOptions(): array
    {
        return collect(LoginProvider::cases())
            ->mapWithKeys(fn (LoginProvider $provider): array => [$provider->value => $provider->label()])
            ->all();
    }
}
