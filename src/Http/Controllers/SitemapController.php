<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Http\Controllers;

use CmsOrbit\Core\Frontend\SitemapGenerator;
use Illuminate\Http\Response;

/**
 * Sitemap Controller
 * 
 * Sitemap.xml 생성 및 제공
 */
class SitemapController extends Controller
{
    /**
     * Generate and return sitemap
     *
     * @return Response
     */
    public function index(): Response
    {
        $xml = SitemapGenerator::generate();

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}

