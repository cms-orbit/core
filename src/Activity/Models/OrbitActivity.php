<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Activity\Models;

use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Filters\Types\Like;
use CmsOrbit\Core\Filters\Types\WhereDateStartEnd;
use CmsOrbit\Core\Filters\Types\WhereIn;
use CmsOrbit\Core\Screen\AsSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable activity/audit record for Orbit core.
 *
 * @property int|null                  $instance_id
 * @property string                    $category
 * @property string                    $event
 * @property string|null               $description
 * @property string|null               $subject_type
 * @property string|null               $subject_id
 * @property string|null               $subject_label
 * @property string|null               $causer_type
 * @property string|null               $causer_id
 * @property string|null               $causer_label
 * @property string|null               $auth_identifier
 * @property string|null               $ip_address
 * @property string|null               $ip_hash
 * @property string|null               $browser_family
 * @property string|null               $device_type
 * @property string|null               $user_agent
 * @property array<string, mixed>|null $properties
 */
class OrbitActivity extends Model
{
    use AsSource, Filterable;

    public const CATEGORY_MODEL = 'model';

    public const CATEGORY_AUTH = 'auth';

    public const CATEGORY_SECURITY = 'security';

    protected $table = 'orbit_activities';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @var array<string, class-string>
     */
    protected $allowedFilters = [
        'category'        => WhereIn::class,
        'event'           => WhereIn::class,
        'description'     => Like::class,
        'auth_identifier' => Like::class,
        'causer_id'       => WhereIn::class,
        'subject_id'      => WhereIn::class,
        'browser_family'  => WhereIn::class,
        'device_type'     => WhereIn::class,
        'created_at'      => WhereDateStartEnd::class,
    ];

    /**
     * @var string[]
     */
    protected $allowedSorts = [
        'created_at',
        'category',
        'event',
        'auth_identifier',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'instance_id' => 'integer',
            'properties'  => 'array',
            'created_at'  => 'datetime',
        ];
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForInstance(Builder $query, ?int $instanceId): Builder
    {
        return $query->when($instanceId !== null, fn (Builder $builder) => $builder->where('instance_id', $instanceId));
    }

    public function scopeLoginHistory(Builder $query): Builder
    {
        return $query->whereIn('category', [
            self::CATEGORY_AUTH,
            self::CATEGORY_SECURITY,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function eventOptions(): array
    {
        return [
            'created'          => 'Created',
            'updated'          => 'Updated',
            'deleted'          => 'Deleted',
            'restored'         => 'Restored',
            'force_deleted'    => 'Force Deleted',
            'login_succeeded'  => 'Login Succeeded',
            'login_failed'     => 'Login Failed',
            'logged_out'       => 'Logged Out',
            'locked_out'       => 'Locked Out',
            'password_changed' => 'Password Changed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function loginHistoryEventOptions(): array
    {
        return collect(static::eventOptions())
            ->only([
                'login_succeeded',
                'login_failed',
                'logged_out',
                'locked_out',
                'password_changed',
            ])
            ->all();
    }
}
