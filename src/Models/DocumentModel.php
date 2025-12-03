<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models;

use CmsOrbit\Core\Models\Concerns\HasCounters;
use CmsOrbit\Core\Models\Concerns\HasDocument;
use CmsOrbit\Core\UI\AsSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Document Model
 * 
 * 문서형 엔티티를 위한 베이스 모델
 * 다국어 콘텐츠, 슬러그, SEO, 조회수/추천수, 댓글, 작성자 정보 지원
 * 
 * @property-read int $document_id
 * @property-read string $document_uuid
 * @property-read string $instance_type
 * @property-read int $instance_id
 * @property-read string|null $author_type
 * @property-read int|null $author_id
 * @property-read int|null $parent_id
 * @property-read string|null $thumbnail
 * @property-read string|null $writer
 * @property-read string|null $email
 * @property-read string|null $certify_key
 * @property-read int $read_count
 * @property-read int $comment_count
 * @property-read int $assent_count
 * @property-read int $dissent_count
 * @property-read bool $is_notice
 * @property-read bool $is_secret
 * @property-read int $approved
 * @property-read string|null $ipaddress
 * @property-read int $sort_order
 * @property-read string|null $locale
 * @property-read string|null $title
 * @property-read string|null $slug
 * @property-read string|null $description
 * @property-read string|null $content
 * @property-read string|null $pure_content
 * @property-read string|null $format
 */
class DocumentModel extends Model
{
    use AsSource;
    use HasFactory;
    use HasCounters;
    use LogsActivity;
    use HasDocument;

    /**
     * Hidden attributes
     *
     * @var array<string>
     */
    protected $hidden = ['certify_key'];

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
        'approved' => 'integer', // 0:rejected, 10:waiting, 30:approved
        'public_at' => 'datetime',
    ];

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
     * Document columns that will be handled by the documents table
     *
     * @var array<string>
     */
    public array $documentColumns = [
        'document_id',
        'document_uuid',
        'instance_type',
        'instance_id',
        'author_type',
        'author_id',
        'parent_id',
        'thumbnail',
        'writer',
        'email',
        'certify_key',
        'read_count',
        'comment_count',
        'assent_count',
        'dissent_count',
        'is_notice',
        'is_secret',
        'approved',
        'ipaddress',
        'sort_order',
        'save_contents',
    ];

    /**
     * Build sort query
     *
     * @return Builder
     */
    public function buildSortQuery(): Builder
    {
        return static::query()->where('instance_type', self::class);
    }

    /**
     * Override newQuery to automatically join documents and document_contents tables
     *
     * @param bool $excludeDeleted
     * @return Builder
     */
    public function newQuery($excludeDeleted = true): Builder
    {
        $query = parent::newQuery($excludeDeleted);

        $currentLocale = app()->getLocale();
        $fallbackLocale = app()->getFallbackLocale();

        // Subquery for preferred content based on locale priority
        $sub = DB::table('document_contents')
            ->select('document_id as doc_id', 'locale', 'title', 'content', 'description', 'format', 'pure_content', 'slug')
            ->selectRaw("
                ROW_NUMBER() OVER (
                    PARTITION BY document_id
                    ORDER BY
                        CASE
                            WHEN locale = ? THEN 0
                            WHEN locale = ? THEN 1
                            ELSE 2
                        END
                ) as rn
            ", [$currentLocale, $fallbackLocale]);

        // Join documents table
        $query->leftJoin('documents', function (JoinClause $join) {
            $join->on('documents.instance_id', '=', sprintf("%s.%s", static::getTable(), static::getKeyName()))
                ->where('documents.instance_type', '=', static::class);
        });

        // Join document_contents with priority
        $query->leftJoinSub($sub, 'preferred_contents', function ($join) {
            $join->on('preferred_contents.doc_id', '=', 'documents.document_id')
                ->where('preferred_contents.rn', '=', 1);
        });

        return $query;
    }

    /**
     * Boot the model
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static $documentFill = [];

        // Before saving, extract document columns
        self::saving(function ($documentModel) use (&$documentFill) {
            foreach ($documentModel->documentColumns as $column) {
                if (isset($documentModel->{$column})) {
                    $documentFill[$column] = $documentModel->{$column};
                    unset($documentModel->{$column});
                }
            }
        });

        // After saved, sync to documents table
        self::saved(function ($documentModel) use (&$documentFill) {
            $condition = [
                'instance_id' => $documentModel->getKey(),
                'instance_type' => $documentModel::class,
            ];

            DB::table('documents')->updateOrInsert($condition, array_merge(
                $documentFill,
                $condition
            ));

            $documentFill = [];
        });
    }

    /**
     * Get the read count column name
     *
     * @return string
     */
    public function getReadCountColumn(): string
    {
        return 'read_count';
    }

    /**
     * Get the assent count column name
     *
     * @return string
     */
    public function getAssentCountColumn(): string
    {
        return 'assent_count';
    }

    /**
     * Get the dissent count column name
     *
     * @return string
     */
    public function getDissentCountColumn(): string
    {
        return 'dissent_count';
    }
}

