<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models\Concerns;

use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\SEOData;

/**
 * Has SEO Trait
 * 
 * Model에서 SEOData를 자동 생성
 */
trait HasSeo
{
    /**
     * Convert model to SEOData
     *
     * @return SEOData
     */
    public function toSeoData(): SEOData
    {
        return new SEOData(
            title: $this->getSeoTitle(),
            description: $this->getSeoDescription(),
            author: $this->getSeoAuthor(),
            image: $this->getSeoImage(),
            published_time: $this->getSeoPublishedTime(),
            modified_time: $this->getSeoModifiedTime(),
            type: $this->getSeoType(),
            canonical_url: $this->getSeoCanonicalUrl(),
        );
    }

    /**
     * Get SEO title
     *
     * @return string|null
     */
    protected function getSeoTitle(): ?string
    {
        return $this->getAttribute('title') 
            ?? $this->getAttribute('name')
            ?? config('orbit.seo.site_name');
    }

    /**
     * Get SEO description
     *
     * @return string|null
     */
    protected function getSeoDescription(): ?string
    {
        if ($description = $this->getAttribute('description')) {
            return $description;
        }

        $content = $this->getAttribute('pure_content') 
                   ?? strip_tags($this->getAttribute('content') ?? '');

        return $content ? Str::limit($content, 160) : config('orbit.seo.description');
    }

    /**
     * Get SEO author
     *
     * @return string|null
     */
    protected function getSeoAuthor(): ?string
    {
        if ($author = $this->getAttribute('author')) {
            return $author->getAttribute('name') ?? null;
        }

        return $this->getAttribute('writer') ?? config('orbit.seo.author');
    }

    /**
     * Get SEO image
     *
     * @return string|null
     */
    protected function getSeoImage(): ?string
    {
        return $this->getAttribute('thumbnail') ?? config('orbit.seo.default_image');
    }

    /**
     * Get SEO published time
     *
     * @return \Carbon\Carbon|null
     */
    protected function getSeoPublishedTime(): ?\Carbon\Carbon
    {
        return $this->getAttribute('public_at') ?? $this->getAttribute('created_at');
    }

    /**
     * Get SEO modified time
     *
     * @return \Carbon\Carbon|null
     */
    protected function getSeoModifiedTime(): ?\Carbon\Carbon
    {
        return $this->getAttribute('updated_at');
    }

    /**
     * Get SEO type
     *
     * @return string
     */
    protected function getSeoType(): string
    {
        return 'article';
    }

    /**
     * Get SEO canonical URL
     *
     * @return string|null
     */
    protected function getSeoCanonicalUrl(): ?string
    {
        if ($slug = $this->getAttribute('slug')) {
            return url($slug);
        }

        return null;
    }
}

