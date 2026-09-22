<?php

/**
 * Valida URLs de medios servidos desde el disco public de la aplicación.
 * Impide que dominios externos suplanten rutas /storage/ de la app.
 */

namespace App\Support;

class StorageMediaUrl
{
    /**
     * Devuelve el host normalizado definido en config('app.url').
     */
    public static function appHost(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return strtolower((string) $host);
    }

    /**
     * Comprueba si la URL apunta a un archivo bajo /storage/ del mismo host que la app.
     */
    public static function isStorageUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        // Rutas relativas /storage/ son del mismo origen y no requieren host.
        if (str_starts_with($url, '/storage/')) {
            return ! str_contains($url, '..');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $appHost = self::appHost();

        if ($appHost === '' || $host !== $appHost) {
            return false;
        }

        return str_starts_with($path, '/storage/') && ! str_contains($path, '..');
    }

    /**
     * Permite medios locales en /storage/ del host de la app o GIFs oficiales de GIPHY.
     */
    public static function isAllowed(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        if (GiphyUrl::isAllowed($url)) {
            return true;
        }

        return self::isStorageUrl($url);
    }

    /**
     * Devuelve la URL de storage validada o null si el host o la ruta no son de confianza.
     */
    public static function sanitizeStorageOnly(?string $url): ?string
    {
        $url = trim((string) $url);

        return self::isStorageUrl($url) ? $url : null;
    }

    /**
     * Devuelve la URL validada (storage local o GIPHY) o null si no está permitida.
     */
    public static function sanitize(?string $url): ?string
    {
        $url = trim((string) $url);

        return self::isAllowed($url) ? $url : null;
    }
}
