<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Document;

use CmsOrbit\Core\Attachment\Attachable;
use CmsOrbit\Core\Document\Models\Document;
use CmsOrbit\Core\Document\Models\DocumentContent;
use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Screen\AsSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Opt-in base model for front-facing content types. A model extending this gains
 * the central documents/document_contents storage: an automatic locale-aware
 * join on read, and split-save on write (document meta → documents, localized
 * body → document_contents, type-specific columns stay on the child table).
 *
 * Note: the "untouched vanilla model" principle applies to the admin layer
 * (Entity never forces inheritance). Document-type models opt into this engine
 * deliberately.
 */
abstract class DocumentModel extends Model
{
    use AsSource, Attachable, Filterable, HasCounters, HasDocument, HasFactory;

    /**
     * Columns persisted to the central documents table.
     *
     * @var array<int, string>
     */
    public array $documentColumns = [
        'document_uuid',
        'document_type',
        'instance_id',
        'parent_id',
        'author_type',
        'author_id',
        'writer',
        'email',
        'certify_key',
        'thumbnail',
        'read_count',
        'comment_count',
        'assent_count',
        'dissent_count',
        'is_notice',
        'is_secret',
        'approved',
        'ipaddress',
        'public_at',
        'sort_order',
    ];

    /**
     * Localized columns persisted to document_contents.
     *
     * @var array<int, string>
     */
    public array $contentColumns = [
        'title',
        'slug',
        'description',
        'content',
        'format',
        'pure_content',
    ];

    /**
     * Stash of split-out values, keyed by object id, between saving and saved.
     *
     * @var array<int, array{document: array<string, mixed>, contents: array<string, array<string, mixed>>}>
     */
    protected static array $splitStash = [];

    /**
     * Cached check for the documents table presence.
     */
    protected static ?bool $documentsTableExists = null;

    /**
     * Cached child-table column listings, keyed by model class.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static array $childColumnsCache = [];

    /**
     * The document type key (defaults to the model class).
     */
    public function documentType(): string
    {
        return static::class;
    }

    /**
     * Auto-join the central document + preferred-locale content on every query,
     * and merge their columns back onto the model so a DocumentModel reads like
     * a single flat record (e.g. `$post->title`, `$post->read_count`) without
     * the caller ever selecting joined columns by hand.
     *
     * Column ownership is derived from {@see $documentColumns}/{@see $contentColumns}
     * (no per-query listing). When a name also exists on the child table the
     * child wins and the joined copy is skipped, so overlapping columns never
     * collide. Timestamps/soft-deletes are pulled from the documents table only
     * when the child table doesn't define them.
     */
    public function newQuery($excludeDeleted = true): Builder
    {
        $query = parent::newQuery($excludeDeleted);

        if (! $this->documentsTableExists()) {
            return $query;
        }

        $table = $this->getTable();
        $currentLocale = app()->getLocale();
        $fallbackLocale = app()->getFallbackLocale();

        $sub = DocumentContent::query()
            ->select(array_merge(['document_id as doc_id', 'locale'], $this->contentColumns))
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY document_id ORDER BY CASE WHEN locale = ? THEN 0 WHEN locale = ? THEN 1 ELSE 2 END) as rn', [$currentLocale, $fallbackLocale]);

        $query->leftJoin('documents', function (JoinClause $join) use ($table) {
            $join->on('documents.documentable_id', '=', $table.'.'.$this->getKeyName())
                ->where('documents.documentable_type', '=', static::class);
        });

        $query->leftJoinSub($sub, 'preferred_contents', function (JoinClause $join) {
            $join->on('preferred_contents.doc_id', '=', 'documents.document_id')
                ->where('preferred_contents.rn', '=', 1);
        });

        $childColumns = $this->childColumns();

        $query->select($table.'.*');

        $documentSelectable = array_merge(
            ['document_id'],
            $this->documentColumns,
            ['created_at', 'updated_at', 'deleted_at'],
        );

        foreach (array_unique($documentSelectable) as $column) {
            if (! in_array($column, $childColumns, true)) {
                $query->addSelect('documents.'.$column);
            }
        }

        foreach ($this->contentColumns as $column) {
            if (! in_array($column, $childColumns, true)) {
                $query->addSelect('preferred_contents.'.$column);
            }
        }

        return $query;
    }

    protected function documentsTableExists(): bool
    {
        return static::$documentsTableExists ??= Schema::hasTable('documents');
    }

    /**
     * Column names physically present on the child table (cached per class).
     *
     * @return array<int, string>
     */
    protected function childColumns(): array
    {
        return static::$childColumnsCache[static::class] ??= Schema::getColumnListing($this->getTable());
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->fillAuthorAndIp();
            $model->splitForSave();
        });

        static::saved(function (self $model) {
            $model->persistDocument();
        });
    }

    /**
     * Populate author/ip/uuid defaults when missing.
     */
    protected function fillAuthorAndIp(): void
    {
        if (empty($this->document_uuid)) {
            $this->document_uuid = (string) Str::uuid();
        }

        if (empty($this->document_type)) {
            $this->document_type = $this->documentType();
        }

        if (empty($this->ipaddress)) {
            $this->ipaddress = request()->ip();
        }

        if (empty($this->author_id) && Auth::check()) {
            $this->author_id = Auth::id();
            $this->author_type = Auth::user()->getMorphClass();
        }
    }

    /**
     * Pull document/content columns out of the model attributes so the child
     * save only persists its own columns.
     */
    protected function splitForSave(): void
    {
        $documentFill = [];
        foreach ($this->documentColumns as $column) {
            if (array_key_exists($column, $this->attributes)) {
                $documentFill[$column] = $this->attributes[$column];
                unset($this->attributes[$column]);
            }
        }

        $contents = $this->save_contents ?? [];
        unset($this->attributes['save_contents']);

        // When no explicit multi-locale payload is given, build one for the
        // current locale from any localized attributes present.
        if ($contents === []) {
            $localeContent = [];
            foreach ($this->contentColumns as $column) {
                if (array_key_exists($column, $this->attributes)) {
                    $localeContent[$column] = $this->attributes[$column];
                    unset($this->attributes[$column]);
                }
            }

            if ($localeContent !== []) {
                $contents[app()->getLocale()] = $localeContent;
            }
        }

        static::$splitStash[spl_object_id($this)] = [
            'document' => $documentFill,
            'contents' => $contents,
        ];
    }

    /**
     * Write the central document row and its localized contents.
     */
    protected function persistDocument(): void
    {
        $stash = static::$splitStash[spl_object_id($this)] ?? ['document' => [], 'contents' => []];
        unset(static::$splitStash[spl_object_id($this)]);

        $document = Document::query()->updateOrCreate(
            [
                'documentable_id' => $this->getKey(),
                'documentable_type' => static::class,
            ],
            array_merge($stash['document'], [
                'document_type' => $this->document_type ?? $this->documentType(),
            ])
        );

        foreach ($stash['contents'] as $locale => $content) {
            $content['slug'] = $this->resolveSlug($content['slug'] ?? ($content['title'] ?? null), $document->document_id, $locale);

            DocumentContent::query()->updateOrCreate(
                ['document_id' => $document->document_id, 'locale' => $locale],
                $content
            );
        }

        $this->rehydrateSplitValues($document, $stash);
    }

    /**
     * Re-attach the values that {@see splitForSave()} stripped off so the model
     * reads back its document/content fields immediately after saving, without
     * a reload. They are synced as original (never dirty) and are removed again
     * on the next save, so they are never written to the child table.
     *
     * @param  array{document: array<string, mixed>, contents: array<string, array<string, mixed>>}  $stash
     */
    protected function rehydrateSplitValues(Document $document, array $stash): void
    {
        $merged = array_merge(
            ['document_id' => $document->document_id],
            $stash['document'],
            $stash['contents'][app()->getLocale()] ?? (reset($stash['contents']) ?: []),
        );

        $childColumns = $this->childColumns();

        foreach ($merged as $column => $value) {
            if (! in_array($column, $childColumns, true)) {
                $this->setAttribute($column, $value);
                $this->syncOriginalAttribute($column);
            }
        }
    }

    /**
     * Generate a unique slug, appending a numeric suffix on collision.
     */
    protected function resolveSlug(?string $candidate, int $documentId, string $locale): string
    {
        $base = Str::slug((string) ($candidate ?: $this->document_uuid ?: Str::random(8)));
        $slug = $base;
        $suffix = 1;

        while (
            DocumentContent::query()
                ->where('slug', $slug)
                ->where('document_id', '!=', $documentId)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
