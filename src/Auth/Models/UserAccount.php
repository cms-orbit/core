<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Models;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use CmsOrbit\Core\Foundation\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int                       $id
 * @property int                       $user_id
 * @property string                    $provider
 * @property string|null               $identifier
 * @property string|null               $normalized_identifier
 * @property string|null               $provider_user_id
 * @property bool                      $is_primary
 * @property Carbon|null               $verified_at
 * @property array<string, mixed>|null $meta
 * @property string|null               $access_token
 * @property string|null               $refresh_token
 * @property Carbon|null               $last_used_at
 */
class UserAccount extends Model
{
    protected $table = 'user_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'identifier',
        'normalized_identifier',
        'provider_user_id',
        'is_primary',
        'verified_at',
        'meta',
        'access_token',
        'refresh_token',
        'last_used_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'is_primary'    => 'bool',
        'verified_at'   => 'datetime',
        'meta'          => 'array',
        'access_token'  => 'encrypted',
        'refresh_token' => 'encrypted',
        'last_used_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $account): void {
            $provider = LoginProvider::from($account->provider);
            $account->normalized_identifier = LoginIdentifierNormalizer::normalize(
                $provider,
                $account->identifier,
            );
        });

        static::saved(function (self $account): void {
            if ($account->provider !== LoginProvider::Email->value) {
                return;
            }

            $account->user?->projectPrimaryEmailAccountToUser();
        });

        static::deleted(function (self $account): void {
            if ($account->provider !== LoginProvider::Email->value) {
                return;
            }

            $account->user?->projectPrimaryEmailAccountToUser();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo($this->resolveUserModelClass(), 'user_id');
    }

    #[Scope]
    protected function provider(Builder $query, LoginProvider|string $provider): void
    {
        $query->where('provider', is_string($provider) ? $provider : $provider->value);
    }

    #[Scope]
    protected function identifier(Builder $query, LoginProvider|string $provider, ?string $identifier): void
    {
        $query->where('provider', is_string($provider) ? $provider : $provider->value)
            ->where('normalized_identifier', LoginIdentifierNormalizer::normalize($provider, $identifier));
    }

    public function markAsUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    public function label(): string
    {
        $provider = LoginProvider::from($this->provider);
        $identifier = $this->identifier ?: $this->provider_user_id ?: (string) $this->getKey();

        return sprintf('%s: %s', $provider->label(), $identifier);
    }

    /**
     * @return class-string<Model>
     */
    protected function resolveUserModelClass(): string
    {
        $guard = (string) config('orbit.guard', config('auth.defaults.guard', 'web'));
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = is_string($provider)
            ? config("auth.providers.{$provider}.model")
            : null;

        return is_string($modelClass) && class_exists($modelClass)
            ? $modelClass
            : User::class;
    }
}
