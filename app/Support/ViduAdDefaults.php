<?php

/**
 * Valores por defecto de la configuración de pausas publicitarias Vidu.
 */

namespace App\Support;

class ViduAdDefaults
{
    /**
     * Configuración global de pausas publicitarias en reels.
     * Los creativos activos rotan aleatoriamente; no hay un único "activo".
     */
    public static function settings(): array
    {
        return [
            'enabled' => false,
            // a quién afectan los anuncios según el video
            'apply_mode' => 'all',        // all | creator_vip | only_selected | none
            // quién ve los anuncios según el espectador
            'viewer_mode' => 'everyone',  // everyone | non_vip
            'min_video_seconds' => 60,
            'trigger_min_seconds' => 20,
            'trigger_max_percent' => 75,
            'sidebar_banners_enabled' => false,
        ];
    }
}
