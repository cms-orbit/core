<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key-value JSON row backing a stored configuration value.
 *
 * @property string $key
 * @property int|null $instance_id
 * @property mixed $value
 */
class OrbitConfig extends Model
{
    protected $table = 'orbit_configs';

    protected $fillable = [
        'key',
        'instance_id',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
        'instance_id' => 'integer',
    ];

    /**
     * Orbit configuration is host-owned. Under SaaS multi-database isolation the
     * default connection is swapped to the active instance database during
     * instance requests, so pin this model to the host connection (falling back
     * to the default connection when SaaS isolation is not configured).
     */
    public function getConnectionName(): ?string
    {
        return config('saas.database.host_connection') ?? $this->connection;
    }
}
