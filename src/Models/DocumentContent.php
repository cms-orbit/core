<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document Content
 * 
 * 다국어 콘텐츠를 저장하는 모델
 */
class DocumentContent extends Model
{
    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'document_contents';

    /**
     * Guarded attributes
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Document relationship
     *
     * @return BelongsTo
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }
}

