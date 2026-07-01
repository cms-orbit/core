<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Seo;

use Closure;

/**
 * Collects URL sources contributed by entities/documents and renders them into
 * a sitemap.xml document. A source is a closure (or array) yielding entries of
 * the shape ['loc' => string, 'lastmod' => ?string, 'changefreq' => ?string,
 * 'priority' => ?string].
 */
class SitemapRegistry
{
    /**
     * @var array<int, Closure|iterable>
     */
    protected array $sources = [];

    /**
     * Register a URL source.
     *
     * @param  Closure|iterable<int, array<string, mixed>>  $source
     */
    public function register(Closure|iterable $source): static
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * Flatten every source into a single list of normalized entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->sources as $source) {
            $items = $source instanceof Closure ? $source() : $source;

            foreach ($items as $item) {
                if (is_string($item)) {
                    $item = ['loc' => $item];
                }

                if (! empty($item['loc'])) {
                    $entries[] = $item;
                }
            }
        }

        return $entries;
    }

    /**
     * Render the registered entries as a sitemap.xml string.
     */
    public function toXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($this->entries() as $entry) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($entry['loc']).'</loc>'."\n";

            foreach (['lastmod', 'changefreq', 'priority'] as $optional) {
                if (! empty($entry[$optional])) {
                    $xml .= '    <'.$optional.'>'.e($entry[$optional]).'</'.$optional.'>'."\n";
                }
            }

            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
