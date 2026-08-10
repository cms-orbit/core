<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserAccountManager
{
    public function __construct(
        protected LoginMethodRegistry $registry,
    ) {}

    /**
     * @param array{emails?: array<int, array{address?: ?string, is_primary?: bool}>, primary_email?: ?string, extra_emails?: ?string, login_id?: ?string, phone?: ?string, email_verified?: bool, phone_verified?: bool} $payload
     */
    public function syncManagedAccounts(Model $user, array $payload): void
    {
        $emailAccounts = isset($payload['emails']) && is_array($payload['emails'])
            ? $this->normalizeStructuredEmails($payload['emails'])
            : $this->normalizeEmails(
                primaryEmail: $payload['primary_email'] ?? null,
                extraEmails: $payload['extra_emails'] ?? null,
            );

        $loginId = $this->normalizeId($payload['login_id'] ?? null);
        $phone = LoginIdentifierNormalizer::normalize(LoginProvider::Phone, $payload['phone'] ?? null);

        $this->assertAtLeastOneEnabledIdentifier($user, $emailAccounts, $loginId, $phone);
        $this->syncEmailAccounts($user, $emailAccounts, (bool) ($payload['email_verified'] ?? false));
        $this->syncSingleLocalAccount($user, LoginProvider::Id, $loginId, true);
        $this->syncSingleLocalAccount($user, LoginProvider::Phone, $phone, (bool) ($payload['phone_verified'] ?? false));

        if (method_exists($user, 'projectPrimaryEmailAccountToUser')) {
            $user->projectPrimaryEmailAccountToUser();
        }
    }

    public function upsertSocialAccount(
        Model $user,
        LoginProvider $provider,
        string $providerUserId,
        ?string $identifier = null,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        array $meta = [],
    ): UserAccount {
        return UserAccount::query()->updateOrCreate(
            [
                'provider'         => $provider->value,
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id'               => $user->getKey(),
                'identifier'            => $identifier,
                'normalized_identifier' => LoginIdentifierNormalizer::normalize($provider, $identifier),
                'verified_at'           => now(),
                'meta'                  => $meta,
                'access_token'          => $accessToken,
                'refresh_token'         => $refreshToken,
                'last_used_at'          => now(),
            ],
        );
    }

    /**
     * @param array<int, array{address?: ?string, is_primary?: bool}> $emails
     *
     * @return array{primary: ?string, all: array<int, string>}
     */
    protected function normalizeStructuredEmails(array $emails): array
    {
        $primary = null;
        $all = [];

        foreach ($emails as $row) {
            if (! is_array($row)) {
                continue;
            }

            $address = LoginIdentifierNormalizer::normalize(
                LoginProvider::Email,
                is_string($row['address'] ?? null) ? $row['address'] : null,
            );

            if ($address === null) {
                continue;
            }

            $all[] = $address;

            if (($row['is_primary'] ?? false) === true) {
                $primary = $address;
            }
        }

        $all = collect($all)->unique()->values()->all();

        if ($primary === null && $all !== []) {
            $primary = $all[0];
        }

        return [
            'primary' => $primary,
            'all'     => $all,
        ];
    }

    /**
     * @return array{primary: ?string, all: array<int, string>}
     */
    protected function normalizeEmails(?string $primaryEmail, ?string $extraEmails): array
    {
        $primary = LoginIdentifierNormalizer::normalize(LoginProvider::Email, $primaryEmail);
        $extraList = preg_split('/[\s,]+/', (string) str_replace(["\r", "\n"], ',', (string) $extraEmails)) ?: [];

        $all = collect(array_filter([
            $primary,
            ...$extraList,
        ]))
            ->map(fn ($email) => LoginIdentifierNormalizer::normalize(LoginProvider::Email, (string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'primary' => $primary,
            'all'     => $all,
        ];
    }

    protected function normalizeId(?string $identifier): ?string
    {
        $normalizedIdentifier = LoginIdentifierNormalizer::normalize(LoginProvider::Id, $identifier);

        if ($normalizedIdentifier === null) {
            return null;
        }

        $length = mb_strlen($normalizedIdentifier);

        if ($length < $this->registry->idMinLength() || $length > $this->registry->idMaxLength()) {
            throw ValidationException::withMessages([
                'login_id' => __('The login ID must be between :min and :max characters.', [
                    'min' => $this->registry->idMinLength(),
                    'max' => $this->registry->idMaxLength(),
                ]),
            ]);
        }

        if (in_array($normalizedIdentifier, $this->registry->blockedIds(), true)) {
            throw ValidationException::withMessages([
                'login_id' => __('This login ID is not available.'),
            ]);
        }

        return $normalizedIdentifier;
    }

    protected function assertAtLeastOneEnabledIdentifier(Model $user, array $emails, ?string $loginId, ?string $phone): void
    {
        $hasLocalIdentifier = ($emails['primary'] ?? null) !== null
            || $loginId !== null
            || $phone !== null;

        if ($hasLocalIdentifier) {
            return;
        }

        $existingSocialAccounts = UserAccount::query()
            ->where('user_id', $user->getKey())
            ->whereIn('provider', [
                LoginProvider::Google->value,
                LoginProvider::Kakao->value,
                LoginProvider::Apple->value,
            ])
            ->exists();

        if ($existingSocialAccounts) {
            return;
        }

        throw ValidationException::withMessages([
            'accounts' => __('Provide credentials for at least one enabled login method.'),
        ]);
    }

    /**
     * @param array{primary: ?string, all: array<int, string>} $emails
     */
    protected function syncEmailAccounts(Model $user, array $emails, bool $verified): void
    {
        /** @var Collection<int, UserAccount> $existing */
        $existing = UserAccount::query()
            ->where('user_id', $user->getKey())
            ->provider(LoginProvider::Email)
            ->get();

        $keep = [];

        foreach ($emails['all'] as $email) {
            $account = $existing->firstWhere('normalized_identifier', $email);

            if ($account === null) {
                $account = UserAccount::query()->create([
                    'user_id'               => $user->getKey(),
                    'provider'              => LoginProvider::Email->value,
                    'identifier'            => $email,
                    'normalized_identifier' => $email,
                    'is_primary'            => $emails['primary'] === $email,
                    'verified_at'           => $verified ? now() : null,
                ]);
            } else {
                $account->forceFill([
                    'identifier'            => $email,
                    'normalized_identifier' => $email,
                    'is_primary'            => $emails['primary'] === $email,
                    'verified_at'           => $verified ? ($account->verified_at ?? now()) : null,
                ])->save();
            }

            $keep[] = $account->getKey();
        }

        if ($keep !== []) {
            UserAccount::query()
                ->where('user_id', $user->getKey())
                ->provider(LoginProvider::Email)
                ->whereNotIn('id', $keep)
                ->delete();
        } elseif ($existing->isNotEmpty()) {
            UserAccount::query()
                ->where('user_id', $user->getKey())
                ->provider(LoginProvider::Email)
                ->delete();
        }
    }

    protected function syncSingleLocalAccount(Model $user, LoginProvider $provider, ?string $identifier, bool $verified): void
    {
        $query = UserAccount::query()
            ->where('user_id', $user->getKey())
            ->provider($provider);

        if ($identifier === null) {
            $query->delete();

            return;
        }

        $account = $query->first();

        if ($account === null) {
            UserAccount::query()->create([
                'user_id'               => $user->getKey(),
                'provider'              => $provider->value,
                'identifier'            => $identifier,
                'normalized_identifier' => $identifier,
                'verified_at'           => $verified ? now() : null,
            ]);

            return;
        }

        $account->forceFill([
            'identifier'            => $identifier,
            'normalized_identifier' => $identifier,
            'verified_at'           => $verified ? ($account->verified_at ?? now()) : null,
        ])->save();
    }
}
