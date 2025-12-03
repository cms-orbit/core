<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models;

use CmsOrbit\Core\UI\AsSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;

/**
 * Dynamic Model
 *
 * 기본 CRUD 엔티티를 위한 베이스 모델
 * UUID, SoftDeletes, Translations, Sorting, ActivityLog 지원
 */
class DynamicModel extends Model implements Sortable
{
    use HasUuids;
    use AsSource;
    use SoftDeletes;
    use HasFactory;
    use HasTranslations;
    use LogsActivity;
    use SortableTrait;

    /**
     * Guarded attributes
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * Translatable attributes
     *
     * @var array<string>
     */
    public array $translatable = ['title'];

    /**
     * Appended attributes
     *
     * @var array<string>
     */
    protected $appends = [
        'created_at_formatted',
        'updated_at_formatted',
    ];

    /**
     * Get the columns that should receive a unique identifier
     *
     * @return array<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * The sortable configuration
     *
     * @var array<string, mixed>
     */
    public array $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
        'sort_on_has_many' => true,
    ];

    /**
     * Get formatted created_at attribute
     *
     * @return string|null
     */
    public function getCreatedAtFormattedAttribute(): ?string
    {
        return $this->getAttribute('created_at')?->diffForHumans();
    }

    /**
     * Get formatted updated_at attribute
     *
     * @return string|null
     */
    public function getUpdatedAtFormattedAttribute(): ?string
    {
        return $this->getAttribute('updated_at')?->diffForHumans();
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

