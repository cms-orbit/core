<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Seo;

/**
 * Converts a normalized SEO array (as produced by Entity::seo()) into a flat map
 * of meta tags: title/description plus Open Graph, Twitter, canonical and robots.
 */
class SeoMeta
{
    /**
     * @param  array<string, mixed>  $seo
     * @param  array<string, mixed>  $options  url, type, site_name, robots overrides
     * @return array<string, string>
     */
    public static function build(array $seo, array $options = []): array
    {
        $title = (string) ($seo['title'] ?? orbit_config('seo.site_title', config('app.name')));
        $description = (string) ($seo['description'] ?? orbit_config('seo.site_description', ''));
        $image = $seo['thumbnail'] ?? orbit_config('seo.default_thumbnail');
        $url = $options['url'] ?? request()->url();
        $type = $options['type'] ?? 'website';
        $siteName = $options['site_name'] ?? orbit_config('seo.site_title', config('app.name'));
        $robots = $options['robots'] ?? orbit_config('seo.robots', 'index,follow');

        $meta = [
            'title' => $title,
            'description' => $description,
            'canonical' => (string) $url,
            'robots' => (string) $robots,
            'og:title' => $title,
            'og:description' => $description,
            'og:type' => (string) $type,
            'og:url' => (string) $url,
            'og:site_name' => (string) $siteName,
            'twitter:card' => $image ? 'summary_large_image' : 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
        ];

        if ($image) {
            $meta['og:image'] = (string) $image;
            $meta['twitter:image'] = (string) $image;
        }

        return array_filter($meta, fn ($value) => $value !== '' && $value !== null);
    }
}
