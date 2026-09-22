<?php

/**
 * Catálogo de motivos válidos para denunciar contenido o usuarios.
 */

namespace App\Support;

class ReportReasons
{

    /**
     * Devuelve el catálogo de motivos de denuncia con clave y etiqueta.
     */
    public static function all(): array
    {
        return [
            ['key' => 'repetitive_post', 'label' => 'Post repetitivo / spam'],
            ['key' => 'nudity', 'label' => 'Desnudos o contenido sexual'],
            ['key' => 'minors', 'label' => 'Menores de edad'],
            ['key' => 'scam', 'label' => 'Estafa o fraude'],
            ['key' => 'illegal_sale', 'label' => 'Venta ilegal'],
            ['key' => 'harassment', 'label' => 'Acoso o bullying'],
            ['key' => 'hate', 'label' => 'Odio o discriminación'],
            ['key' => 'violence', 'label' => 'Violencia o amenazas'],
            ['key' => 'copyright', 'label' => 'Derechos de autor'],
            ['key' => 'other', 'label' => 'Otro motivo'],
        ];
    }

    /**
     * Devuelve solo las claves válidas de motivos de denuncia.
     */
    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }

    /**
     * Devuelve la etiqueta legible de un motivo dado su clave.
     */
    public static function label(string $key): string
    {
        foreach (self::all() as $reason) {
            if ($reason['key'] === $key) {
                return $reason['label'];
            }
        }

        return $key;
    }

    /**
     * Comprueba si una clave de motivo de denuncia es válida.
     */
    public static function isValid(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }
}
