<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Document;

use CmsOrbit\Core\Document\Models\Document;
use CmsOrbit\Core\Document\Models\DocumentContent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Relations linking a concrete content model to the central documents tables.
 */
trait HasDocument
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * The central document row that mirrors this model.
     */
    public function document(): MorphOne
    {
        return $this->morphOne(Document::class, 'documentable');
    }

    /**
     * All locale content rows for this model's document.
     */
    public function contents(): HasMany
    {
        return $this->hasMany(DocumentContent::class, 'document_id', 'document_id');
    }
}
