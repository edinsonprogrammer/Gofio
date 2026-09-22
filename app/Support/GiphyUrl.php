<?php

/**
 * Valida URLs de medios servidos por la CDN oficial de GIPHY.
 */

namespace App\Support;

class GiphyUrl
{
    /** Hosts permitidos para GIFs embebidos desde GIPHY. */
    private const ALLOWED_HOSTS = [
        'media.giphy.com',
        'media0.giphy.com',
        'media1.giphy.com',
        'media2.giphy.com',
        'media3.giphy.com',
        'media4.giphy.com',
        'i.giphy.com',
    ];

    /**
     * Comprueba si la URL apunta a un recurso multimedia alojado en GIPHY.
     */
    public static function isAllowed(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true) || ! in_array($host, self::ALLOWED_HOSTS, true)) {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return $path !== '' && (
            str_contains($path, '/media/')
            || preg_match('#^/[A-Za-z0-9_-]+\.(gif|webp|mp4)$#', $path) === 1
        );
    }

    /**
     * Normaliza y valida una URL de GIPHY; devuelve cadena vacía si no es válida.
     */
    public static function sanitize(string $url): string
    {
        return self::isAllowed($url) ? trim($url) : '';
    }
}
