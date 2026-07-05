<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Enums;

enum LoginProvider: string
{
    case Id = 'id';
    case Email = 'email';
    case Phone = 'phone';
    case Kakao = 'kakao';
    case Apple = 'apple';
    case Google = 'google';

    public function isLocal(): bool
    {
        return in_array($this, [self::Id, self::Email, self::Phone], true);
    }

    public function isSocial(): bool
    {
        return ! $this->isLocal();
    }

    public function label(): string
    {
        return match ($this) {
            self::Id     => __('ID login'),
            self::Email  => __('Email'),
            self::Phone  => __('Phone'),
            self::Kakao  => __('Kakao'),
            self::Apple  => __('Apple'),
            self::Google => __('Google'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $provider): string => $provider->value,
            self::cases(),
        );
    }

    /**
     * @return array<int, self>
     */
    public static function local(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $provider): bool => $provider->isLocal()));
    }

    /**
     * @return array<int, self>
     */
    public static function social(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $provider): bool => $provider->isSocial()));
    }
}
