<?php

/**
 * Catálogo estático de beneficios mostrados en la página de suscripción Creator Plus.
 */

namespace App\Support;

class CreatorPlusBenefits
{

    /**
     * Devuelve el listado completo de beneficios Creator Plus con icono y descripción.
     */
    public static function all(): array
    {
        return [
            [
                'icon' => 'fa-solid fa-ban',
                'title' => 'Sin publicidad',
                'description' => 'Navega Gofio sin banners ni espacios publicitarios.',
            ],
            [
                'icon' => 'fa-solid fa-circle-check',
                'title' => 'Check azul verificado',
                'description' => 'Badge Creator Plus visible en tu perfil, posts y comentarios.',
            ],
            [
                'icon' => 'fa-solid fa-palette',
                'title' => 'Personalización de apariencia',
                'description' => 'Cambia colores y skins de la interfaz. Incluye el tema exclusivo dorado de Creator Plus.',
            ],
            [
                'icon' => 'fa-solid fa-bolt',
                'title' => 'Karma x2 en votos recibidos',
                'description' => 'Duplica el karma que recibes cuando otros votan tu contenido.',
            ],
            [
                'icon' => 'fa-solid fa-wand-magic-sparkles',
                'title' => 'Posts resaltados con brillo',
                'description' => 'Tus publicaciones lucen un resplandor dorado en el feed mientras la suscripción esté activa.',
            ],
            [
                'icon' => 'fa-solid fa-border-all',
                'title' => 'Borde brillante en posts',
                'description' => 'Marco luminoso alrededor de cada post para destacarte visualmente.',
            ],
            [
                'icon' => 'fa-solid fa-headset',
                'title' => 'Soporte prioritario a moderación',
                'description' => 'Botón en tu perfil para enviar quejas o problemas con atención preferente del staff.',
            ],
            [
                'icon' => 'fa-solid fa-eye',
                'title' => 'Visitas a tu perfil',
                'description' => 'Recibe notificaciones de quién visitó tu perfil (exclusivo Creator Plus).',
            ],
        ];
    }

    /**
     * Devuelve solo los títulos de los beneficios.
     */
    public static function labels(): array
    {
        return array_column(self::all(), 'title');
    }
}
