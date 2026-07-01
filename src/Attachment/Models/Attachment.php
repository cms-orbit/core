<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Attachment\Models;

use CmsOrbit\Core\Attachment\MimeTypes;
use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Filters\Types\Like;
use CmsOrbit\Core\Foundation\Concerns\Sortable;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\AsSource;
use CmsOrbit\Core\Support\Facades\Orbit;
use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class Attachment.
 */
class Attachment extends Model
{
    use AsSource, Filterable, HasFactory, HasUuids, Sortable;

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'original_name',
        'mime',
        'extension',
        'size',
        'path',
        'sort',
        'hash',
        'disk',
        'group',
        'kind',
        'width',
        'height',
        'duration',
        'alt',
        'description',
        'encoding_status',
        'meta',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'url',
        'relativeUrl',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'sort' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'meta' => 'array',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'name' => Like::class,
        'original_name' => Like::class,
        'mime' => Like::class,
        'extension' => Like::class,
        'disk' => Like::class,
        'group' => Like::class,
    ];

    /**
     * @var array
     */
    protected $allowedSorts = [
        'name',
        'original_name',
        'mime',
        'extension',
        'disk',
        'group',
    ];

    /**
     * Get the column name for sorting.
     */
    public function getSortColumnName(): string
    {
        return 'sort';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Orbit::model(User::class));
    }

    /**
     * Return the address by which you can access the file.
     */
    public function url(?string $default = null): ?string
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->getAttribute('disk'));
        $path = $this->physicalPath();

        return $path !== null && $disk->exists($path)
            ? $disk->url($path)
            : $default;
    }

    public function getUrlAttribute(): ?string
    {
        return $this->url();
    }

    public function getRelativeUrlAttribute(): ?string
    {
        $url = $this->url();

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return parse_url($url, PHP_URL_PATH) ?: null;
    }

    public function getTitleAttribute(): ?string
    {
        if ($this->original_name !== 'blob') {
            return $this->original_name;
        }

        return $this->name.'.'.$this->extension;
    }

    public function physicalPath(): ?string
    {
        if ($this->path === null || $this->name === null) {
            return null;
        }

        return $this->path.$this->name.'.'.$this->extension;
    }

    /**
     * @return bool|null
     *
     * @throws Exception
     */
    public function delete()
    {
        if ($this->exists) {
            if (static::where('hash', $this->hash)->where('disk', $this->disk)->limit(2)->count() <= 1) {
                // Physical removal a file.
                Storage::disk($this->disk)->delete($this->physicalPath());
            }
            $this->relationships()->delete();
        }

        return parent::delete();
    }

    /**
     * @return HasMany
     */
    public function relationships()
    {
        return $this->hasMany(Orbit::model(Attachmentable::class), 'attachment_id');
    }

    /**
     * Get MIME type for file.
     */
    public function getMimeType(): string
    {
        $mimes = new MimeTypes;

        $type = $mimes->getMimeType($this->getAttribute('extension'));

        return $type ?? 'unknown';
    }

    public function isMime(string $type): bool
    {
        return Str::of($this->mime)->is($type);
    }

    public function isPhysicalExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->physicalPath());
    }

    /**
     * @return mixed
     */
    public function download(array $headers = [])
    {
        return Storage::disk($this->disk)
            ->download($this->physicalPath(), $this->original_name, $headers);
    }
}
