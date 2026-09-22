<?php

/**
 * Rutas públicas de SEO: robots.txt, sitemaps, llms.txt, RSS e IndexNow.
 */

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function __construct(
        private readonly SeoService $seoService,
    ) {}

    /**
     * GET /robots.txt — política de rastreo para buscadores e IAs.
     */
    public function robots(): Response
    {
        return response($this->seoService->robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * GET /sitemap.xml — índice de sitemaps del sitio.
     */
    public function sitemapIndex(): Response
    {
        return response($this->seoService->sitemapIndexXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * GET /sitemaps/{name} — sitemap parcial (posts, perfiles, estáticas).
     */
    public function sitemapPart(string $name): Response
    {
        $xml = $this->seoService->sitemapPartXml($name);

        abort_unless($xml, 404);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * GET /llms.txt y /ai.txt — guía legible para crawlers de IA.
     */
    public function llmsTxt(): Response
    {
        return response($this->seoService->llmsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * GET /feed.xml — RSS de posts recientes.
     */
    public function rssFeed(): Response
    {
        return response($this->seoService->rssFeedXml(), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    /**
     * GET /{key}.txt — verificación de clave IndexNow.
     */
    public function indexNowKey(string $key): Response
    {
        abort_unless($key === $this->seoService->indexNowKey(), 404);

        return response($key, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
