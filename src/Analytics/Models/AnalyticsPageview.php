<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics\Models;

use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Filters\Types\Like;
use CmsOrbit\Core\Filters\Types\WhereDateStartEnd;
use CmsOrbit\Core\Filters\Types\WhereIn;
use CmsOrbit\Core\Screen\AsSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A single lightweight pageview hit captured for Orbit's built-in analytics.
 *
 * @property int|null    $instance_id
 * @property string|null $user_id
 * @property string|null $user_type
 * @property string|null $user_name
 * @property string|null $user_email
 * @property string      $visitor_hash
 * @property string      $visit_token
 * @property bool        $is_entrance
 * @property string|null $route_name
 * @property string|null $route_uri
 * @property string      $page_path
 * @property string|null $referrer_host
 * @property string|null $ip_address
 * @property string|null $country_code
 * @property string|null $browser_family
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property bool        $is_bot
 * @property Carbon      $visited_on
 */
class AnalyticsPageview extends Model
{
    use AsSource, Filterable;

    protected $table = 'orbit_analytics_pageviews';

    protected $guarded = [];

    /**
     * @var array<string, class-string>
     */
    protected $allowedFilters = [
        'user_id'        => WhereIn::class,
        'user_email'     => Like::class,
        'visitor_hash'   => Like::class,
        'browser_family' => WhereIn::class,
        'device_type'    => WhereIn::class,
        'country_code'   => WhereIn::class,
        'route_name'     => Like::class,
        'route_uri'      => Like::class,
        'page_path'      => Like::class,
        'referrer_host'  => Like::class,
        'ip_address'     => Like::class,
        'created_at'     => WhereDateStartEnd::class,
    ];

    /**
     * @var string[]
     */
    protected $allowedSorts = [
        'created_at',
        'user_email',
        'browser_family',
        'device_type',
        'country_code',
        'route_name',
        'page_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'instance_id' => 'integer',
            'is_entrance' => 'boolean',
            'is_bot'      => 'boolean',
            'visited_on'  => 'date',
        ];
    }
}
