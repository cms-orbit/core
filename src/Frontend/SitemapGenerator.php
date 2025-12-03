<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Frontend;

use CmsOrbit\Core\Models\Concerns\HasSitemap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * Sitemap Generator
 * 
 * 엔티티를 자동 스캔하여 sitemap.xml 생성
 */
class SitemapGenerator
{
    /**
     * Generate sitemap
     *
     * @return string XML content
     */
    public static function generate(): string
    {
        $entities = static::discoverEntities();
        $urls = [];

        foreach ($entities as $entityClass) {
            $urls = array_merge($urls, static::getEntityUrls($entityClass));
        }

        return static::buildXml($urls);
    }

    /**
     * Discover entities with HasSitemap trait
     *
     * @return array
     */
    protected static function discoverEntities(): array
    {
        $entities = [];
        $entitiesPath = app_path('Orbit/Entities');

        if (!File::exists($entitiesPath)) {
            return $entities;
        }

        $entityDirs = File::directories($entitiesPath);

        foreach ($entityDirs as $entityPath) {
            $entityName = basename($entityPath);
            $modelFile = "{$entityPath}/{$entityName}.php";

            if (!File::exists($modelFile)) {
                continue;
            }

            $namespace = app()->getNamespace() . "Orbit\\Entities\\{$entityName}\\{$entityName}";

            if (!class_exists($namespace)) {
                continue;
            }

            // Check if model uses HasSitemap trait
            $uses = class_uses_recursive($namespace);
            
            if (in_array(HasSitemap::class, $uses)) {
                $entities[] = $namespace;
            }
        }

        return $entities;
    }

    /**
     * Get URLs from entity
     *
     * @param string $entityClass
     * @return array
     */
    protected static function getEntityUrls(string $entityClass): array
    {
        try {
            $items = $entityClass::getSitemapItems();
            $urls = [];

            foreach ($items as $item) {
                $urls[] = [
                    'loc' => $item->getSitemapItemUrl(),
                    'lastmod' => $item->getSitemapItemLastModified()->toW3cString(),
                    'changefreq' => $item->getSitemapItemChangeFrequency(),
                    'priority' => $item->getSitemapItemPriority(),
                ];
            }

            return $urls;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Build XML
     *
     * @param array $urls
     * @return string
     */
    protected static function buildXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }
}

