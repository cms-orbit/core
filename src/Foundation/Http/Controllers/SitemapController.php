<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Seo\SitemapRegistry;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Serve the generated sitemap.xml. Uses a cached copy when present.
     */
    public function index(SitemapRegistry $registry): Response
    {
        $cached = storage_path('app/orbit/sitemap.xml');

        $xml = is_file($cached)
            ? (string) file_get_contents($cached)
            : $registry->toXml();

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
