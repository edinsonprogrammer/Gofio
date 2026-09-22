<?php

/**
 * Servicio central de SEO: metadatos, sitemaps, IndexNow y ping a buscadores.
 */

namespace App\Services;

use App\Jobs\NotifySearchEnginesJob;
use App\Models\Post;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SeoService
{
    private const SITEMAP_CACHE_TTL = 900;

    public function __construct(
        private readonly SiteSettingsRepository $siteSettings,
    ) {}

    /**
     * Metadatos por defecto del sitio (home, login, etc.).
     */
    public function defaultMeta(?string $canonical = null): array
    {
        $settings = $this->siteSettings->all();
        $title = $settings['seo_meta_title'] ?? 'Gofio! es la tonica!';
        $description = $settings['seo_meta_description'] ?? '';
        $keywords = $settings['seo_meta_keywords'] ?? '';
        $siteName = $settings['site_title'] ?? 'Gofio';
        $url = $canonical ?? url('/login');
        $image = $this->absoluteImageUrl($settings['seo_og_image'] ?? null);

        return $this->buildMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $url,
            'image' => $image,
            'site_name' => $siteName,
            'type' => 'website',
            'json_ld' => $this->organizationJsonLd($settings, $url),
        ]);
    }

    /**
     * Metadatos SEO para una publicación indexable.
     */
    public function forPost(Post $post): array
    {
        $settings = $this->siteSettings->all();
        $siteName = $settings['site_title'] ?? 'Gofio';
        $description = Str::limit($this->excerptFromPost($post), 160, '…');
        $url = route('posts.show', $post->slug);
        $title = "{$post->title} | {$siteName}";

        return $this->buildMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => $this->keywordsForPost($post, $settings),
            'canonical' => $url,
            'image' => $this->postImageUrl($post) ?? $this->absoluteImageUrl($settings['seo_og_image'] ?? null),
            'site_name' => $siteName,
            'type' => 'article',
            'json_ld' => [
                '@context' => 'https://schema.org',
                '@type' => 'DiscussionForumPosting',
                'headline' => $post->title,
                'description' => $description,
                'url' => $url,
                'datePublished' => $post->created_at?->toAtomString(),
                'dateModified' => $post->updated_at?->toAtomString(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $post->user->username ?? 'Usuario Gofio',
                    'url' => isset($post->user) ? route('profile.show', $post->user->username) : $url,
                ],
                'publisher' => $this->publisherNode($settings),
                'interactionStatistic' => [
                    [
                        '@type' => 'InteractionCounter',
                        'interactionType' => 'https://schema.org/CommentAction',
                        'userInteractionCount' => (int) $post->comments_count,
                    ],
                ],
                'inLanguage' => 'es-CO',
            ],
        ]);
    }

    /**
     * Metadatos SEO para un perfil público.
     */
    public function forProfile(User $user): array
    {
        $settings = $this->siteSettings->all();
        $siteName = $settings['site_title'] ?? 'Gofio';
        $url = route('profile.show', $user->username);
        $description = Str::limit(
            "Perfil de @{$user->username} en {$siteName}. Karma: {$user->karma}. Comunidad colombiana.",
            160,
            '…',
        );
        $title = "@{$user->username} | {$siteName}";

        return $this->buildMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => $settings['seo_meta_keywords'] ?? '',
            'canonical' => $url,
            'image' => $user->avatar_url ?: $this->absoluteImageUrl($settings['seo_og_image'] ?? null),
            'site_name' => $siteName,
            'type' => 'profile',
            'json_ld' => [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'name' => "@{$user->username}",
                'url' => $url,
                'description' => $description,
                'inLanguage' => 'es-CO',
                'mainEntity' => [
                    '@type' => 'Person',
                    'name' => $user->username,
                    'identifier' => $user->username,
                    'url' => $url,
                    'image' => $user->avatar_url,
                ],
            ],
        ]);
    }

    /**
     * Payload compartido con Inertia para identidad del sitio y SEO global.
     */
    public function sharePayload(): array
    {
        $settings = $this->siteSettings->all();

        return [
            'site_title' => $settings['site_title'] ?? 'Gofio',
            'site_slogan' => $settings['site_slogan'] ?? '',
            'site_copyright' => $settings['site_copyright'] ?? 'Gofio © 2026 — EdsonDev',
            'site_logo_url' => $settings['site_logo_url'] ?? '',
            'default' => $this->defaultMeta(),
        ];
    }

    /**
     * Contenido de robots.txt dinámico y amigable con crawlers.
     */
    public function robotsTxt(): string
    {
        $settings = $this->siteSettings->all();
        $sitemap = url('/sitemap.xml');
        $extra = trim((string) ($settings['seo_robots_extra'] ?? ''));

        $lines = [
            '# Gofio — robots.txt',
            '# Bienvenidos crawlers de buscadores, IAs y archivos.',
            '',
            'User-agent: *',
            'Allow: /',
            'Allow: /login',
            'Allow: /registro',
            'Allow: /post/',
            'Allow: /perfil/',
            'Allow: /sitemap.xml',
            'Allow: /sitemaps/',
            'Allow: /llms.txt',
            'Allow: /ai.txt',
            'Allow: /feed.xml',
            '',
            'User-agent: Googlebot',
            'Allow: /',
            '',
            'User-agent: Bingbot',
            'Allow: /',
            '',
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: ChatGPT-User',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            "Sitemap: {$sitemap}",
        ];

        if ($extra !== '') {
            $lines[] = '';
            $lines[] = $extra;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * llms.txt / ai.txt — guía para crawlers de IA.
     */
    public function llmsTxt(): string
    {
        $settings = $this->siteSettings->all();
        $title = $settings['seo_meta_title'] ?? 'Gofio! es la tonica!';
        $description = $settings['seo_meta_description'] ?? '';
        $base = rtrim(config('app.url'), '/');

        return implode("\n", [
            '# '.$title,
            '',
            '> '.Str::limit($description, 500, '…'),
            '',
            'Gofio es una red social colombiana con posts, perfiles, karma, medallas y comunidad.',
            '',
            '## Páginas principales',
            "- [Inicio / Login]({$base}/login)",
            "- [Registro]({$base}/registro)",
            "- [Mapa del sitio XML]({$base}/sitemap.xml)",
            "- [Feed RSS]({$base}/feed.xml)",
            '',
            '## Contenido dinámico',
            "- Posts públicos: {$base}/post/{slug}",
            "- Perfiles: {$base}/perfil/{username}",
            '',
            '## Contacto',
            '- Email: '.($settings['site_email'] ?? 'admin@gofio.test'),
            '',
            '## Idioma',
            '- es-CO (español colombiano)',
            '',
        ])."\n";
    }

    /**
     * Notifica buscadores cuando se publica un post indexable.
     */
    public function notifyPublishedPost(Post $post): void
    {
        if ($post->status !== 'published' || $post->is_private) {
            return;
        }

        $url = route('posts.show', $post->slug);
        $this->forgetSitemapCache();
        NotifySearchEnginesJob::dispatch($url, 'post', $post->id);
    }

    /**
     * Clave IndexNow (generada y persistida en ajustes del sitio).
     */
    public function indexNowKey(): string
    {
        $existing = $this->siteSettings->get('seo_indexnow_key');

        if (is_string($existing) && strlen($existing) === 32) {
            return $existing;
        }

        $key = bin2hex(random_bytes(16));
        $this->siteSettings->set('seo_indexnow_key', $key);

        return $key;
    }

    /**
     * Envía URL a IndexNow y ping de sitemap (síncrono desde job).
     */
    public function submitUrlToSearchEngines(string $url): void
    {
        if (! $this->siteSettings->get('seo_indexnow_enabled', true)) {
            return;
        }

        $key = $this->indexNowKey();
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $keyLocation = url("/{$key}.txt");

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $keyLocation,
            'urlList' => [$url],
        ];

        foreach (['https://api.indexnow.org/indexnow', 'https://www.bing.com/indexnow'] as $endpoint) {
            try {
                Http::timeout(8)->post($endpoint, $payload);
            } catch (\Throwable) {
                // Indexación best-effort; no bloquea la publicación.
            }
        }

        $sitemapUrl = urlencode(url('/sitemap.xml'));
        foreach ([
            "https://www.bing.com/ping?sitemap={$sitemapUrl}",
        ] as $pingUrl) {
            try {
                Http::timeout(8)->get($pingUrl);
            } catch (\Throwable) {
                //
            }
        }
    }

    /**
     * Invalida caché de sitemaps tras cambios de contenido.
     */
    public function forgetSitemapCache(): void
    {
        Cache::forget('gofio:seo:sitemap:index');
        for ($i = 1; $i <= 50; $i++) {
            Cache::forget("gofio:seo:sitemap:static");
            Cache::forget("gofio:seo:sitemap:posts:{$i}");
            Cache::forget("gofio:seo:sitemap:profiles:{$i}");
        }
    }

    /**
     * Construye el índice sitemap.xml.
     */
    public function sitemapIndexXml(): string
    {
        return Cache::remember('gofio:seo:sitemap:index', self::SITEMAP_CACHE_TTL, function () {
            $entries = [
                $this->sitemapLoc(url('/sitemaps/static.xml'), now()),
            ];

            $postsPerPage = 1000;
            $postsCount = Post::query()->published()->count();
            $postPages = max(1, (int) ceil($postsCount / $postsPerPage));

            for ($page = 1; $page <= $postPages; $page++) {
                $entries[] = $this->sitemapLoc(url("/sitemaps/posts-{$page}.xml"), now());
            }

            $profilesPerPage = 1000;
            $profilesCount = User::query()->where('is_banned', false)->count();
            $profilePages = max(1, (int) ceil($profilesCount / $profilesPerPage));

            for ($page = 1; $page <= $profilePages; $page++) {
                $entries[] = $this->sitemapLoc(url("/sitemaps/profiles-{$page}.xml"), now());
            }

            return $this->wrapSitemapIndex($entries);
        });
    }

    /**
     * Genera un sitemap parcial por nombre.
     */
    public function sitemapPartXml(string $name): ?string
    {
        if ($name === 'static.xml') {
            return Cache::remember('gofio:seo:sitemap:static', self::SITEMAP_CACHE_TTL, fn () => $this->staticSitemapXml());
        }

        if (preg_match('/^posts-(\\d+)\\.xml$/', $name, $m)) {
            return Cache::remember("gofio:seo:sitemap:posts:{$m[1]}", self::SITEMAP_CACHE_TTL, fn () => $this->postsSitemapXml((int) $m[1]));
        }

        if (preg_match('/^profiles-(\\d+)\\.xml$/', $name, $m)) {
            return Cache::remember("gofio:seo:sitemap:profiles:{$m[1]}", self::SITEMAP_CACHE_TTL, fn () => $this->profilesSitemapXml((int) $m[1]));
        }

        return null;
    }

    /**
     * Feed RSS de posts recientes para syndication e indexación.
     */
    public function rssFeedXml(): string
    {
        return Cache::remember('gofio:seo:rss', 600, function () {
            $settings = $this->siteSettings->all();
            $title = $settings['seo_meta_title'] ?? 'Gofio';
            $description = $settings['seo_meta_description'] ?? '';
            $link = url('/login');
            $posts = Post::query()
                ->published()
                ->with('user:id,username')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            $items = $posts->map(function (Post $post) {
                $url = route('posts.show', $post->slug);
                $desc = htmlspecialchars($this->excerptFromPost($post), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $title = htmlspecialchars($post->title, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $pub = $post->created_at?->toRfc2822String() ?? now()->toRfc2822String();

                return "<item><title>{$title}</title><link>{$url}</link><guid isPermaLink=\"true\">{$url}</guid><description>{$desc}</description><pubDate>{$pub}</pubDate></item>";
            })->implode('');

            $escTitle = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $escDesc = htmlspecialchars($description, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            return '<?xml version="1.0" encoding="UTF-8"?>'
                .'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'
                .'<channel>'
                ."<title>{$escTitle}</title>"
                ."<link>{$link}</link>"
                ."<description>{$escDesc}</description>"
                .'<language>es-co</language>'
                .'<atom:link href="'.url('/feed.xml').'" rel="self" type="application/rss+xml"/>'
                .$items
                .'</channel></rss>';
        });
    }

    private function staticSitemapXml(): string
    {
        $urls = [
            [url('/login'), now(), 'daily', '1.0'],
            [url('/registro'), now(), 'monthly', '0.8'],
        ];

        return $this->wrapUrlSet($urls);
    }

    private function postsSitemapXml(int $page): string
    {
        $perPage = 1000;
        $posts = Post::query()
            ->published()
            ->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['slug', 'updated_at']);

        $urls = $posts->map(fn (Post $post) => [
            route('posts.show', $post->slug),
            $post->updated_at ?? now(),
            'weekly',
            '0.9',
        ])->all();

        return $this->wrapUrlSet($urls);
    }

    private function profilesSitemapXml(int $page): string
    {
        $perPage = 1000;
        $users = User::query()
            ->where('is_banned', false)
            ->orderByDesc('updated_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['username', 'updated_at']);

        $urls = $users->map(fn (User $user) => [
            route('profile.show', $user->username),
            $user->updated_at ?? now(),
            'weekly',
            '0.7',
        ])->all();

        return $this->wrapUrlSet($urls);
    }

    private function wrapSitemapIndex(array $entries): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $entries)
            .'</sitemapindex>';
    }

    private function sitemapLoc(string $loc, $lastmod): string
    {
        $date = $lastmod instanceof \DateTimeInterface ? $lastmod->format('c') : now()->format('c');

        return '<sitemap><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc><lastmod>'.$date.'</lastmod></sitemap>';
    }

    private function wrapUrlSet(array $urls): string
    {
        $body = collect($urls)->map(function ($row) {
            [$loc, $lastmod, $freq, $priority] = $row;
            $date = $lastmod instanceof \DateTimeInterface ? $lastmod->format('c') : now()->format('c');

            return '<url>'
                .'<loc>'.htmlspecialchars($loc, ENT_XML1).'</loc>'
                .'<lastmod>'.$date.'</lastmod>'
                .'<changefreq>'.$freq.'</changefreq>'
                .'<priority>'.$priority.'</priority>'
                .'</url>';
        })->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$body
            .'</urlset>';
    }

    private function buildMeta(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'keywords' => $data['keywords'],
            'canonical' => $data['canonical'],
            'image' => $data['image'],
            'site_name' => $data['site_name'],
            'type' => $data['type'],
            'robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'json_ld' => $data['json_ld'],
        ];
    }

    private function organizationJsonLd(array $settings, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $settings['site_title'] ?? 'Gofio',
            'alternateName' => $settings['seo_meta_title'] ?? 'Gofio! es la tonica!',
            'url' => $url,
            'description' => $settings['seo_meta_description'] ?? '',
            'inLanguage' => 'es-CO',
            'publisher' => $this->publisherNode($settings),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/buscar').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    private function publisherNode(array $settings): array
    {
        return [
            '@type' => 'Organization',
            'name' => $settings['seo_organization_name'] ?? ($settings['site_title'] ?? 'Gofio'),
            'url' => url('/login'),
            'logo' => $this->siteLogoUrl($settings) ?? $this->absoluteImageUrl($settings['seo_og_image'] ?? null),
            'areaServed' => $settings['seo_organization_country'] ?? 'CO',
        ];
    }

    private function excerptFromPost(Post $post): string
    {
        $blocks = $post->content['blocks'] ?? $post->content ?? [];
        $text = '';

        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (! is_array($block)) {
                    continue;
                }
                $chunk = match ($block['type'] ?? '') {
                    'paragraph', 'header' => strip_tags((string) ($block['data']['text'] ?? '')),
                    'quote' => strip_tags((string) ($block['data']['text'] ?? '')),
                    default => '',
                };
                $text .= ' '.$chunk;
                if (strlen($text) > 200) {
                    break;
                }
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', $text));

        return $text !== '' ? $text : $post->title;
    }

    private function keywordsForPost(Post $post, array $settings): string
    {
        $base = $settings['seo_meta_keywords'] ?? '';
        $tags = trim((string) ($post->tags ?? ''));

        return trim($base.($tags !== '' ? ", {$tags}" : ''));
    }

    private function postImageUrl(Post $post): ?string
    {
        $blocks = $post->content['blocks'] ?? $post->content ?? [];

        if (! is_array($blocks)) {
            return null;
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'image' && ! empty($block['data']['url'])) {
                return $this->absoluteImageUrl($block['data']['url']);
            }
        }

        return null;
    }

    private function absoluteImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    private function siteLogoUrl(array $settings): ?string
    {
        $logo = trim((string) ($settings['site_logo_url'] ?? ''));

        if ($logo === '') {
            return null;
        }

        return $this->absoluteImageUrl($logo);
    }
}
