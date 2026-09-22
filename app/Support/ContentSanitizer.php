<?php

/**
 * Sanitizador de bloques del editor de contenido.
 * Filtra tipos permitidos, limpia HTML y valida URLs.
 */

namespace App\Support;

class ContentSanitizer
{
    /** Hosts permitidos para bloques embed (alineado con embed_services de PostPermissionService). */
    private const ALLOWED_EMBED_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtu.be',
        'www.youtu.be',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'twitter.com',
        'www.twitter.com',
        'mobile.twitter.com',
        'platform.twitter.com',
        'x.com',
        'www.x.com',
        'tiktok.com',
        'www.tiktok.com',
        'vm.tiktok.com',
        'instagram.com',
        'www.instagram.com',
        'vimeo.com',
        'www.vimeo.com',
        'player.vimeo.com',
    ];

    private const ALL_BLOCK_TYPES = [
        'header',
        'paragraph',
        'code',
        'image',
        'gif',
        'embed',
        'quote',
        'list',
        'delimiter',
    ];

    private const ALLOWED_CODE_LANGUAGES = [
        'javascript', 'typescript', 'php', 'python', 'bash', 'html', 'css', 'sql', 'json', 'plaintext',
    ];

    /**
     * Filtra y limpia bloques del editor según tipos permitidos.
     */
    public function sanitize(array $blocks, ?array $allowedTools = null): array
    {
        $allowed = $allowedTools
            ? array_values(array_intersect(self::ALL_BLOCK_TYPES, $allowedTools))
            : self::ALL_BLOCK_TYPES;

        $sanitized = [];

        foreach ($blocks as $block) {
            if (! is_array($block) || empty($block['type']) || ! in_array($block['type'], $allowed, true)) {
                continue;
            }

            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            $item = match ($block['type']) {
                'header' => [
                    'type' => 'header',
                    'data' => [
                        'text' => $this->cleanText($data['text'] ?? '', 500),
                        'level' => in_array((int) ($data['level'] ?? 2), [1, 2, 3, 4], true) ? (int) $data['level'] : 2,
                    ],
                ],
                'paragraph' => [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => $this->cleanRichHtml($data['text'] ?? '', 10000),
                    ],
                ],
                'code' => [
                    'type' => 'code',
                    'data' => [
                        'code' => $this->cleanText($data['code'] ?? '', 20000),
                        'language' => $this->cleanLanguage($data['language'] ?? ''),
                    ],
                ],
                'image' => [
                    'type' => 'image',
                    'data' => [
                        'url' => $this->cleanImageUrl($data['url'] ?? ($data['file']['url'] ?? '')),
                        'caption' => $this->cleanText($data['caption'] ?? '', 300),
                    ],
                ],
                'gif' => [
                    'type' => 'gif',
                    'data' => $this->cleanGifBlock($data),
                ],
                'embed' => [
                    'type' => 'embed',
                    'data' => [
                        'service' => $this->cleanText($data['service'] ?? '', 50),
                        'source' => $this->cleanEmbedUrl($data['source'] ?? ''),
                        'embed' => $this->cleanEmbedUrl($data['embed'] ?? ''),
                        'caption' => $this->cleanText($data['caption'] ?? '', 300),
                    ],
                ],
                'quote' => [
                    'type' => 'quote',
                    'data' => [
                        'text' => $this->cleanText($data['text'] ?? '', 5000),
                        'caption' => $this->cleanText($data['caption'] ?? '', 300),
                        'alignment' => in_array($data['alignment'] ?? 'left', ['left', 'center'], true)
                            ? ($data['alignment'] ?? 'left')
                            : 'left',
                    ],
                ],
                'list' => [
                    'type' => 'list',
                    'data' => [
                        'style' => ($data['style'] ?? 'unordered') === 'ordered' ? 'ordered' : 'unordered',
                        'items' => $this->cleanListItems($data['items'] ?? []),
                    ],
                ],
                'delimiter' => [
                    'type' => 'delimiter',
                    'data' => (object) [],
                ],
                default => null,
            };

            if ($item !== null) {
                if ($item['type'] === 'image' && empty($item['data']['url'])) {
                    continue;
                }
                if ($item['type'] === 'gif' && empty($item['data']['url'])) {
                    continue;
                }
                if ($item['type'] === 'embed' && empty($item['data']['embed']) && empty($item['data']['source'])) {
                    continue;
                }
                if ($item['type'] === 'list' && empty($item['data']['items'])) {
                    continue;
                }
                if ($item['type'] === 'quote' && empty($item['data']['text'])) {
                    continue;
                }

                $sanitized[] = $item;
            }
        }

        return array_values($sanitized);
    }

    /**
     * Sanitiza un bloque GIF de GIPHY con URL e identificador verificados.
     */
    private function cleanGifBlock(array $data): array
    {
        $url = GiphyUrl::sanitize((string) ($data['url'] ?? ''));

        return [
            'url' => $url,
            'giphy_id' => $this->cleanText((string) ($data['giphy_id'] ?? ''), 40),
            'title' => $this->cleanText((string) ($data['title'] ?? ''), 120),
            'preview_url' => GiphyUrl::sanitize((string) ($data['preview_url'] ?? '')) ?: $url,
        ];
    }

    /**
     * Sanitiza cada ítem de una lista del editor.
     */
    private function cleanListItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => $this->cleanRichHtml(is_string($item) ? $item : '', 1000),
            $items
        )));
    }

    /**
     * Sanitiza HTML enriquecido de párrafos: inline seguro, listas y alineación.
     */
    private function cleanRichHtml(string $html, int $maxLength): string
    {
        $html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;
        $html = preg_replace('/on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        $allowed = '<b><i><u><s><strong><em><br><span><ul><ol><li><div><sup><sub>';
        $html = strip_tags($html, $allowed);

        $html = preg_replace_callback(
            '/<(span|div)\s+style="([^"]*)"(\s*\/?)>/i',
            function (array $matches): string {
                $safe = $this->sanitizeInlineStyle($matches[2]);

                if ($safe === '') {
                    return "<{$matches[1]}>";
                }

                return "<{$matches[1]} style=\"{$safe}\">";
            },
            $html
        ) ?? $html;

        return mb_substr(trim($html), 0, $maxLength);
    }

    /**
     * Devuelve un atributo style con propiedades CSS permitidas para contenido de posts.
     */
    private function sanitizeInlineStyle(string $style): string
    {
        $allowed = [
            'color' => '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i',
            'background-color' => '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i',
            'font-family' => '/^[a-z0-9 ,"\'\\-]+$/i',
            'font-size' => '/^\d+(?:\.\d+)?(px|em|rem|%)$/i',
            'font-weight' => '/^(normal|bold|[1-9]00)$/i',
            'font-style' => '/^(normal|italic)$/i',
            'text-decoration' => '/^(none|underline|line-through)$/i',
            'text-align' => '/^(left|center|right|justify)$/i',
            'text-transform' => '/^(none|uppercase|lowercase|capitalize)$/i',
        ];

        $safe = [];

        foreach (explode(';', $style) as $chunk) {
            if (! str_contains($chunk, ':')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $chunk, 2));
            $key = strtolower($key);

            if (! isset($allowed[$key]) || ! preg_match($allowed[$key], $value)) {
                continue;
            }

            $safe[] = "{$key}:{$value}";
        }

        return implode(';', $safe);
    }

    /**
     * Valida el lenguaje de un bloque de código contra la lista permitida.
     */
    private function cleanLanguage(string $language): string
    {
        $language = strtolower(trim($language));

        return in_array($language, self::ALLOWED_CODE_LANGUAGES, true) ? $language : 'plaintext';
    }

    /**
     * Elimina etiquetas peligrosas y trunca texto a la longitud máxima.
     */
    private function cleanText(string $text, int $maxLength): string
    {
        $text = strip_tags($text, '<b><i><u><a><br><strong><em>');
        $text = preg_replace('/javascript\s*:/i', '', $text) ?? $text;

        return mb_substr(trim($text), 0, $maxLength);
    }

    /**
     * Valida URLs de bloques imagen: storage del host de la app o CDN de GIPHY.
     */
    private function cleanImageUrl(string $url): string
    {
        $url = trim(strip_tags($url));

        return StorageMediaUrl::sanitize($url) ?? '';
    }

    /**
     * Valida URLs de embeds contra la lista de hosts de plataformas permitidas.
     */
    private function cleanEmbedUrl(string $url): string
    {
        $url = trim(strip_tags($url));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        if (! in_array($host, self::ALLOWED_EMBED_HOSTS, true)) {
            return '';
        }

        return $url;
    }
}
