<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Document
 * 
 * DocumentModel과 연결되는 중간 테이블 모델
 * 다국어 콘텐츠 및 메타데이터 관리
 */
class Document extends Model
{
    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The primary key for the model
     *
     * @var string
     */
    protected $primaryKey = 'document_id';

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
        'read_count' => 'integer',
        'comment_count' => 'integer',
        'assent_count' => 'integer',
        'dissent_count' => 'integer',
        'is_notice' => 'boolean',
        'is_secret' => 'boolean',
        'approved' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Instance relationship (polymorphic)
     *
     * @return MorphTo
     */
    public function instance(): MorphTo
    {
        return $this->morphTo('instance');
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
     * Contents relationship
     *
     * @return HasMany
     */
    public function contents(): HasMany
    {
        return $this->hasMany(DocumentContent::class, 'document_id', 'document_id');
    }
}

