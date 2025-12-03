<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;

/**
 * Has Document Trait
 * 
 * DocumentModel에서 사용하는 관계 및 메서드
 */
trait HasDocument
{
    /**
     * Parent document relationship
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Children documents relationship
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Author relationship (polymorphic)
     *
     * @return MorphTo
     */
    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    /**
     * Document contents relationship (keyed by locale)
     *
     * @return HasMany
     */
    public function contents(): HasMany
    {
        return $this->hasMany(
            \CmsOrbit\Core\Models\DocumentContent::class,
            'document_id',
            'document_id'
        );
    }

    /**
     * Get the activity log options
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->dontSubmitEmptyLogs()
            ->logOnly(['*'])
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty();
    }
}

