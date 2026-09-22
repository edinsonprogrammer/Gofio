<?php

/**
 * Reglas por defecto del sistema de karma y sus recompensas.
 */

namespace App\Support;

class KarmaRulesDefaults
{

    /**
     * Devuelve las reglas de karma por defecto con karma, etiqueta y flags.
     */
    public static function all(): array
    {
        return [
            'post_created' => [
                'enabled' => true,
                'karma' => 2,
                'label' => 'Publicar un post',
            ],
            'comment_created' => [
                'enabled' => true,
                'karma' => 1,
                'label' => 'Comentar en un post',
            ],
            'profile_completed' => [
                'enabled' => true,
                'karma' => 10,
                'once' => true,
                'label' => 'Completar el perfil',
            ],
            'video_in_post' => [
                'enabled' => true,
                'karma' => 5,
                'once_per_reference' => true,
                'label' => 'Incluir video en un post',
            ],
            'popular_post' => [
                'enabled' => true,
                'karma' => 15,
                'min_reactions' => 10,
                'once_per_reference' => true,
                'label' => 'Post popular (reacciones)',
            ],
            'followers_milestone' => [
                'enabled' => true,
                'karma' => 5,
                'every' => 10,
                'label' => 'Hito de seguidores',
            ],
            'tip_sent' => [
                'enabled' => true,
                'karma' => 1,
                'label' => 'Enviar propina',
            ],
            'tip_received' => [
                'enabled' => false,
                'karma' => 0,
                'label' => 'Recibir propina',
            ],
        ];
    }
}
