<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Document\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single counter event (view / assent / dissent / …) recorded against a
 * countable model.
 */
class CounterLog extends Model
{
    protected $table = 'counter_logs';

    protected $guarded = [];

    public function countable(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
