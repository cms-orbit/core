<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Document\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-locale content row for a document.
 *
 * @property int $content_id
 * @property int $document_id
 * @property string $locale
 * @property string $slug
 */
class DocumentContent extends Model
{
    protected $table = 'document_contents';

    protected $primaryKey = 'content_id';

    protected $guarded = [];

    protected $casts = [
        'format' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }
}
