<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Phone;

use CmsOrbit\Core\Auth\LoginMethodRegistry;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class PhoneChallengeBroker
{
    public function __construct(
        protected CacheRepository $cache,
        protected PhoneVerificationSender $sender,
        protected LoginMethodRegistry $registry,
    ) {}

    /**
     * @return array{identifier: string, expires_at: string}
     */
    public function send(string $identifier): array
    {
        $normalizedIdentifier = LoginIdentifierNormalizer::normalize('phone', $identifier);

        if ($normalizedIdentifier === null) {
            throw new \InvalidArgumentException(__('유효한 휴대폰 번호를 입력해 주세요.'));
        }

        $code = (string) random_int(100000, 999999);
        $ttlSeconds = $this->registry->phoneChallengeTtlSeconds();

        $this->cache->put($this->cacheKey($normalizedIdentifier), [
            'code'       => hash('sha256', $code),
            'identifier' => $normalizedIdentifier,
            'expires_at' => now()->addSeconds($ttlSeconds)->toIso8601String(),
        ], $ttlSeconds);

        $this->sender->send($normalizedIdentifier, $code, $this->registry->phoneVerificationChannel());

        return [
            'identifier' => $normalizedIdentifier,
            'expires_at' => now()->addSeconds($ttlSeconds)->toIso8601String(),
        ];
    }

    public function verify(string $identifier, string $code): bool
    {
        $normalizedIdentifier = LoginIdentifierNormalizer::normalize('phone', $identifier);

        if ($normalizedIdentifier === null) {
            return false;
        }

        $payload = $this->cache->get($this->cacheKey($normalizedIdentifier));

        if (! is_array($payload) || ! isset($payload['code'])) {
            return false;
        }

        $valid = hash_equals((string) $payload['code'], hash('sha256', trim($code)));

        if ($valid) {
            $this->cache->forget($this->cacheKey($normalizedIdentifier));
        }

        return $valid;
    }

    protected function cacheKey(string $identifier): string
    {
        return 'orbit.auth.phone.'.hash('sha256', $identifier);
    }
}
