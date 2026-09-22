<?php

/**
 * Comando Artisan que regenera y calienta la caché de sitemaps SEO.
 */

namespace App\Console\Commands;

use App\Services\SeoService;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'gofio:seo:sitemap';

    protected $description = 'Regenera sitemaps XML y RSS para buscadores e IAs.';

    /**
     * Limpia caché y precalienta el índice y partes principales del sitemap.
     */
    public function handle(SeoService $seoService): int
    {
        $seoService->forgetSitemapCache();
        $seoService->sitemapIndexXml();
        $seoService->sitemapPartXml('static.xml');
        $seoService->sitemapPartXml('posts-1.xml');
        $seoService->sitemapPartXml('profiles-1.xml');
        $seoService->rssFeedXml();

        $this->info('Sitemaps SEO regenerados correctamente.');

        return self::SUCCESS;
    }
}
