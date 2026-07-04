<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Activity;

use CmsOrbit\Core\Activity\Models\OrbitActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * @param array<string, mixed> $properties
     */
    public function log(
        string $event,
        string $category,
        ?string $description = null,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        ?Request $request = null,
        ?string $authIdentifier = null,
    ): ?OrbitActivity {
        if (! $this->activitiesTableExists()) {
            return null;
        }

        $request ??= $this->request();
        $causer ??= $this->currentCauser();
        $requestContext = $this->requestContext($request);

        return OrbitActivity::query()->create([
            'instance_id'     => $this->instanceId(),
            'category'        => $category,
            'event'           => $event,
            'description'     => $description,
            'subject_type'    => $subject?->getMorphClass(),
            'subject_id'      => $subject?->getKey() !== null ? (string) $subject->getKey() : null,
            'subject_label'   => $this->labelFor($subject),
            'causer_type'     => $causer?->getMorphClass(),
            'causer_id'       => $causer?->getKey() !== null ? (string) $causer->getKey() : null,
            'causer_label'    => $this->labelFor($causer),
            'auth_identifier' => $authIdentifier,
            'ip_address'      => $requestContext['ip_address'],
            'ip_hash'         => $requestContext['ip_hash'],
            'browser_family'  => $requestContext['browser_family'],
            'device_type'     => $requestContext['device_type'],
            'user_agent'      => $requestContext['user_agent'],
            'properties'      => $properties !== [] ? $this->sanitizeProperties($properties) : null,
            'created_at'      => now(),
        ]);
    }

    public function logModelCreated(Model $model): ?OrbitActivity
    {
        return $this->log(
            event: 'created',
            category: OrbitActivity::CATEGORY_MODEL,
            description: __('Created :subject', ['subject' => $this->labelFor($model) ?? class_basename($model)]),
            subject: $model,
        );
    }

    /**
     * @param array<string, mixed> $changes
     * @param array<string, mixed> $original
     * @param string[]             $ignored
     */
    public function logModelUpdated(Model $model, array $changes, array $original, array $ignored = []): ?OrbitActivity
    {
        $diff = collect($changes)
            ->except(array_merge(['updated_at'], $ignored))
            ->mapWithKeys(fn ($newValue, string $key) => [
                $key => [
                    'old' => $original[$key] ?? null,
                    'new' => $newValue,
                ],
            ])
            ->all();

        if ($diff === []) {
            return null;
        }

        return $this->log(
            event: 'updated',
            category: OrbitActivity::CATEGORY_MODEL,
            description: __('Updated :subject', ['subject' => $this->labelFor($model) ?? class_basename($model)]),
            subject: $model,
            properties: ['changes' => $diff],
        );
    }

    public function logModelDeleted(Model $model): ?OrbitActivity
    {
        return $this->log(
            event: 'deleted',
            category: OrbitActivity::CATEGORY_MODEL,
            description: __('Deleted :subject', ['subject' => $this->labelFor($model) ?? class_basename($model)]),
            subject: $model,
        );
    }

    public function logModelRestored(Model $model): ?OrbitActivity
    {
        return $this->log(
            event: 'restored',
            category: OrbitActivity::CATEGORY_MODEL,
            description: __('Restored :subject', ['subject' => $this->labelFor($model) ?? class_basename($model)]),
            subject: $model,
        );
    }

    public function logModelForceDeleted(Model $model): ?OrbitActivity
    {
        return $this->log(
            event: 'force_deleted',
            category: OrbitActivity::CATEGORY_MODEL,
            description: __('Permanently deleted :subject', ['subject' => $this->labelFor($model) ?? class_basename($model)]),
            subject: $model,
        );
    }

    public function logPasswordChanged(Model $subject, ?Model $causer = null, bool $forced = false): ?OrbitActivity
    {
        $sameActor = $causer !== null
            && $causer->getMorphClass() === $subject->getMorphClass()
            && (string) $causer->getKey() === (string) $subject->getKey();

        $description = $sameActor
            ? __('Changed password')
            : __('Changed a user password');

        return $this->log(
            event: 'password_changed',
            category: OrbitActivity::CATEGORY_SECURITY,
            description: $description,
            subject: $subject,
            causer: $causer,
            properties: ['forced' => $forced],
        );
    }

    protected function currentCauser(): ?Model
    {
        $guard = auth()->guard((string) config('orbit.guard'));
        $user = $guard->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * @return array{ip_address: ?string, ip_hash: ?string, browser_family: ?string, device_type: ?string, user_agent: ?string}
     */
    protected function requestContext(?Request $request): array
    {
        if ($request === null) {
            return [
                'ip_address'     => null,
                'ip_hash'        => null,
                'browser_family' => null,
                'device_type'    => null,
                'user_agent'     => null,
            ];
        }

        $userAgent = (string) $request->userAgent();
        $parsedAgent = $this->parseUserAgent($userAgent);
        $ipAddress = $request->ip();

        return [
            'ip_address'     => $this->anonymizeIp($ipAddress),
            'ip_hash'        => $ipAddress !== null ? hash_hmac('sha256', $ipAddress, $this->hashKey()) : null,
            'browser_family' => $parsedAgent['browser_family'],
            'device_type'    => $parsedAgent['device_type'],
            'user_agent'     => $userAgent !== '' ? $userAgent : null,
        ];
    }

    protected function labelFor(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }

        foreach (['title', 'name', 'email', 'slug'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && filled($value)) {
                return Str::limit($value, 180);
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    /**
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    protected function sanitizeProperties(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            $sanitized[(string) $key] = $this->sanitizeValue((string) $key, $value);
        }

        return $sanitized;
    }

    protected function sanitizeValue(string $key, mixed $value): mixed
    {
        if ($this->isSensitiveKey($key)) {
            return '[redacted]';
        }

        if (is_array($value)) {
            return collect($value)
                ->mapWithKeys(fn ($nestedValue, $nestedKey) => [
                    (string) $nestedKey => $this->sanitizeValue((string) $nestedKey, $nestedValue),
                ])
                ->all();
        }

        if ($value instanceof \JsonSerializable) {
            return $this->sanitizeValue($key, $value->jsonSerialize());
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Model) {
            return $this->labelFor($value);
        }

        if (is_object($value)) {
            return class_basename($value);
        }

        return $value;
    }

    protected function isSensitiveKey(string $key): bool
    {
        return preg_match('/password|token|secret|remember/i', $key) === 1;
    }

    protected function anonymizeIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $segments = explode(':', $ipAddress);
            $segments = array_pad($segments, 8, '0');

            return implode(':', array_slice($segments, 0, 4)).'::';
        }

        return null;
    }

    /**
     * @return array{browser_family: ?string, device_type: ?string}
     */
    protected function parseUserAgent(string $userAgent): array
    {
        if ($userAgent === '') {
            return ['browser_family' => null, 'device_type' => null];
        }

        $lowered = Str::lower($userAgent);

        $browser = match (true) {
            str_contains($userAgent, 'Edg/')                                             => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')        => 'Opera',
            str_contains($userAgent, 'SamsungBrowser/')                                  => 'Samsung Internet',
            str_contains($userAgent, 'CriOS') || str_contains($userAgent, 'Chrome/')     => 'Chrome',
            str_contains($userAgent, 'FxiOS') || str_contains($userAgent, 'Firefox/')    => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            str_contains($userAgent, 'Trident/') || str_contains($userAgent, 'MSIE ')    => 'Internet Explorer',
            default                                                                      => 'Unknown',
        };

        $deviceType = match (true) {
            str_contains($lowered, 'ipad') || str_contains($lowered, 'tablet')                                        => 'tablet',
            str_contains($lowered, 'mobile') || str_contains($lowered, 'iphone') || str_contains($lowered, 'android') => 'mobile',
            default                                                                                                   => 'desktop',
        };

        return [
            'browser_family' => $browser,
            'device_type'    => $deviceType,
        ];
    }

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
    }

    protected function request(): ?Request
    {
        return app()->bound('request') ? request() : null;
    }

    protected function hashKey(): string
    {
        return (string) (config('app.key') ?: static::class);
    }

    protected function activitiesTableExists(): bool
    {
        return once(fn () => Schema::hasTable('orbit_activities'));
    }
}
