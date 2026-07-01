<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Document\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Central document row shared by every DocumentModel-based content type.
 *
 * @property int $document_id
 * @property string $document_uuid
 * @property string $document_type
 * @property int|null $instance_id
 */
class Document extends Model
{
    use SoftDeletes;

    protected $table = 'documents';

    protected $primaryKey = 'document_id';

    protected $guarded = [];

    protected $hidden = ['certify_key'];

    protected $casts = [
        'read_count' => 'integer',
        'comment_count' => 'integer',
        'assent_count' => 'integer',
        'dissent_count' => 'integer',
        'is_notice' => 'boolean',
        'is_secret' => 'boolean',
        'approved' => 'integer',
        'public_at' => 'datetime',
        'instance_id' => 'integer',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(DocumentContent::class, 'document_id', 'document_id');
    }
}
