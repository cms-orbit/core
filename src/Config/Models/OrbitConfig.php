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
     * Stored configuration is host-level data, so it must stay on the SaaS host
     * connection even while a tenant instance connection is the default. Core
     * reads the config key rather than the SaaS package so the two stay
     * decoupled; without SaaS installed the key is absent and Eloquent falls
     * back to the application's default connection.
     */
    public function getConnectionName(): ?string
    {
        $hostConnection = config('saas.database.host_connection');

        if (is_string($hostConnection) && $hostConnection !== '') {
            return $hostConnection;
        }

        return $this->connection;
    }
}
