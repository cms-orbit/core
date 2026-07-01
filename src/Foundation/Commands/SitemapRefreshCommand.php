<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Seo\SitemapRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:sitemap')]
class SitemapRefreshCommand extends Command
{
    protected $signature = 'orbit:sitemap';

    protected $description = 'Regenerate the cached sitemap.xml from the sitemap registry';

    public function handle(SitemapRegistry $registry): int
    {
        $path = storage_path('app/orbit');

        File::ensureDirectoryExists($path);
        File::put($path.'/sitemap.xml', $registry->toXml());

        $this->info('Sitemap refreshed: '.count($registry->entries()).' url(s).');

        return self::SUCCESS;
    }
}
