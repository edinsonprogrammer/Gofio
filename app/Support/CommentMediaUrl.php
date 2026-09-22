<?php

/**
 * Valida URLs de imágenes adjuntas en comentarios (almacenamiento local o GIPHY).
 */

namespace App\Support;

class CommentMediaUrl
{
    /**
     * Permite imágenes subidas al disco public del host de la app o GIFs oficiales de GIPHY.
     */
    public static function isAllowed(?string $url): bool
    {
        return StorageMediaUrl::isAllowed($url);
    }

    /**
     * Devuelve la URL sanitizada o null si no está permitida.
     */
    public static function sanitize(?string $url): ?string
    {
        return StorageMediaUrl::sanitize($url);
    }
}
