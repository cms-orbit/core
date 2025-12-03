<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Has Sitemap Trait
 * 
 * Sitemap 생성을 위한 메서드 제공
 */
trait HasSitemap
{
    /**
     * Get sitemap items
     *
     * @return Collection
     */
    public function getSitemapItems(): Collection
    {
        return static::query()
            ->whereNotNull('slug')
            ->when(
                method_exists(static::class, 'scopePublished'),
                fn($query) => $query->published()
            )
            ->get();
    }

    /**
     * Get sitemap item URL
     *
     * @return string
     */
    public function getSitemapItemUrl(): string
    {
        return url($this->getAttribute('slug'));
    }

    /**
     * Get sitemap item last modified date
     *
     * @return Carbon
     */
    public function getSitemapItemLastModified(): Carbon
    {
        return $this->getAttribute('updated_at');
    }

    /**
     * Get sitemap item change frequency
     *
     * @return string
     */
    public function getSitemapItemChangeFrequency(): string
    {
        return 'weekly';
    }

    /**
     * Get sitemap item priority
     *
     * @return float
     */
    public function getSitemapItemPriority(): float
    {
        return 0.5;
    }
}

