<?php

/**
 * Valores por defecto de la configuración global del sitio.
 */

namespace App\Support;

class SiteSettingsDefaults
{

    /**
     * Devuelve todos los valores por defecto de configuración del sitio.
     */
    public static function all(): array
    {
        return [
            'site_title' => 'Gofio',
            'site_slogan' => 'Red social comunitaria',
            'site_email' => 'admin@gofio.test',
            'site_copyright' => 'Gofio © 2026 — EdsonDev',
            'site_logo_url' => '',
            'seo_meta_title' => 'Gofio! es la tonica!',
            'seo_meta_description' => 'Es una red social colombiana que agrupa las mejores funciones de las redes sociales tradicionales, con un toque diferencial. Además es un lugar para compartir de todo, desde cosas útiles hasta pasar el rato y hacer amigos.',
            'seo_meta_keywords' => 'la nueva taringa, red social colombiana, gofio, comunidad colombiana, foro colombiano, red social, crear contenido, karma, medallas, comunidad online, compartir posts, hacer amigos, entretenimiento colombia',
            'seo_og_image' => '',
            'seo_twitter_handle' => '',
            'seo_google_site_verification' => '',
            'seo_bing_site_verification' => '',
            'seo_indexnow_enabled' => true,
            'seo_indexnow_key' => '',
            'seo_robots_extra' => '',
            'seo_organization_name' => 'Gofio',
            'seo_organization_country' => 'CO',
            'registration_enabled' => true,
            'offline_mode' => false,
            'offline_message' => 'Estamos en mantenimiento. Vuelve después para que continúe la diversión. Estamos trabajando para que este espacio sea ameno y único para ti.',
            'welcome_message' => 'Hola {username}, bienvenido a Gofio.',
            'max_posts_per_day' => 10,
            'max_comments_per_day' => 30,
            'max_votes_per_day' => 5,
            'featured_votes_threshold' => 50,
            'karma_rules' => KarmaRulesDefaults::all(),
            'allow_tips' => true,
            'allow_uploads' => true,
            'default_rango_id' => 1,
            'platform_fee_percent' => 10,
        ];
    }
}
