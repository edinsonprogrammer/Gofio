<?php

/**
 * Definición de pestañas del panel de administración y permisos por rango staff.
 * Los administradores globales (is_admin) tienen acceso total sin consultar esta lista.
 */

namespace App\Support;

use App\Models\User;

class AdminPermissions
{
    /**
     * Pestañas disponibles en el panel con metadatos para navegación y formularios.
     *
     * @return array<string, array{label: string, href: string, icon: string, exact?: bool, group?: string}>
     */
    public static function definitions(): array
    {
        return [
            'dashboard' => [
                'label' => 'Panel',
                'href' => '/admin',
                'icon' => 'fa-solid fa-gauge-high',
                'exact' => true,
                'group' => 'General',
            ],
            'users' => [
                'label' => 'Usuarios',
                'href' => '/admin/usuarios',
                'icon' => 'fa-solid fa-users',
                'group' => 'Moderación',
            ],
            'posts' => [
                'label' => 'Publicaciones',
                'href' => '/admin/posts',
                'icon' => 'fa-solid fa-newspaper',
                'group' => 'Moderación',
            ],
            'moderation' => [
                'label' => 'Moderación',
                'href' => '/admin/moderacion',
                'icon' => 'fa-solid fa-shield-halved',
                'group' => 'Moderación',
            ],
            'categories' => [
                'label' => 'Categorías',
                'href' => '/admin/categorias',
                'icon' => 'fa-solid fa-folder',
                'group' => 'Contenido',
            ],
            'badwords' => [
                'label' => 'Palabras prohibidas',
                'href' => '/admin/palabras',
                'icon' => 'fa-solid fa-ban',
                'group' => 'Contenido',
            ],
            'tickets' => [
                'label' => 'Soporte',
                'href' => '/admin/tickets',
                'icon' => 'fa-solid fa-life-ring',
                'group' => 'Soporte',
            ],
            'verifications' => [
                'label' => 'Verificaciones',
                'href' => '/admin/verificaciones',
                'icon' => 'fa-solid fa-id-card',
                'group' => 'Soporte',
            ],
            'medals' => [
                'label' => 'Medallas',
                'href' => '/admin/medallas',
                'icon' => 'fa-solid fa-medal',
                'group' => 'Gamificación',
            ],
            'awards' => [
                'label' => 'Premios',
                'href' => '/admin/premios',
                'icon' => 'fa-solid fa-award',
                'group' => 'Gamificación',
            ],
            'settings' => [
                'label' => 'Configuración',
                'href' => '/admin/configuracion',
                'icon' => 'fa-solid fa-gear',
                'group' => 'Administración',
            ],
            'ranks' => [
                'label' => 'Rangos',
                'href' => '/admin/rangos',
                'icon' => 'fa-solid fa-ranking-star',
                'group' => 'Administración',
            ],
            'karma' => [
                'label' => 'Reglas de karma',
                'href' => '/admin/karma',
                'icon' => 'fa-solid fa-star-half-stroke',
                'group' => 'Administración',
            ],
            'tips' => [
                'label' => 'Propinas / Monedas',
                'href' => '/admin/propinas',
                'icon' => 'fa-solid fa-coins',
                'group' => 'Administración',
            ],
            'themes' => [
                'label' => 'Temas / Apariencias',
                'href' => '/admin/temas',
                'icon' => 'fa-solid fa-palette',
                'group' => 'Administración',
            ],
            'icon_packs' => [
                'label' => 'Paquetes de iconos',
                'href' => '/admin/iconos',
                'icon' => 'fa-solid fa-icons',
                'group' => 'Administración',
            ],
            'vidu_ads' => [
                'label' => 'Publicidad Vidu',
                'href' => '/admin/vidu-publicidad',
                'icon' => 'fa-solid fa-rectangle-ad',
                'group' => 'Administración',
            ],
        ];
    }

    /**
     * Claves válidas de pestañas del panel.
     */
    public static function allTabKeys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Permisos por defecto del rango Moderador.
     */
    public static function moderatorDefaults(): array
    {
        return [
            'dashboard',
            'users',
            'posts',
            'moderation',
            'categories',
            'badwords',
            'tickets',
            'verifications',
            'medals',
            'awards',
        ];
    }

    /**
     * Relaciona nombres de rutas Laravel con la pestaña requerida.
     */
    public static function tabForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        $map = [
            'admin.dashboard' => 'dashboard',

            'admin.settings.index' => 'settings',
            'admin.settings.update' => 'settings',
            'admin.settings.logo.upload' => 'settings',
            'admin.settings.logo.remove' => 'settings',

            'admin.users.index' => 'users',
            'admin.users.search' => 'users',
            'admin.users.ban' => 'users',
            'admin.users.unban' => 'users',
            'admin.users.update' => 'users',
            'admin.users.revoke-verification' => 'users',
            'admin.users.revoke-creator-plus' => 'users',

            'admin.posts.index' => 'posts',
            'admin.posts.ban' => 'posts',
            'admin.posts.publish' => 'posts',
            'admin.posts.feature' => 'posts',
            'admin.posts.sticky' => 'posts',

            'admin.moderation.index' => 'moderation',
            'admin.moderation.report' => 'moderation',
            'admin.moderation.report.action' => 'moderation',
            'admin.moderation.report.edit' => 'moderation',
            'admin.moderation.alert' => 'moderation',

            'admin.categories.index' => 'categories',
            'admin.categories.store' => 'categories',
            'admin.categories.update' => 'categories',
            'admin.categories.destroy' => 'categories',

            'admin.badwords.index' => 'badwords',
            'admin.badwords.store' => 'badwords',
            'admin.badwords.destroy' => 'badwords',

            'admin.tickets.index' => 'tickets',
            'admin.tickets.update' => 'tickets',

            'admin.verifications.index' => 'verifications',
            'admin.verifications.bulk' => 'verifications',
            'admin.verifications.approve' => 'verifications',
            'admin.verifications.reject' => 'verifications',
            'admin.verifications.revoke-user' => 'verifications',
            'admin.verifications.document' => 'verifications',

            'admin.karma-rules.index' => 'karma',
            'admin.karma-rules.store' => 'karma',
            'admin.karma-rules.update' => 'karma',
            'admin.karma-rules.destroy' => 'karma',

            'admin.themes.index' => 'themes',
            'admin.themes.sync' => 'themes',
            'admin.themes.store' => 'themes',
            'admin.themes.update' => 'themes',
            'admin.themes.destroy' => 'themes',

            'admin.icon-packs.index' => 'icon_packs',
            'admin.icon-packs.library' => 'icon_packs',
            'admin.icon-packs.sync' => 'icon_packs',

            'admin.ranks.index' => 'ranks',
            'admin.ranks.store' => 'ranks',
            'admin.ranks.update' => 'ranks',
            'admin.ranks.destroy' => 'ranks',
            'admin.ranks.assign' => 'ranks',
            'admin.ranks.unlock' => 'ranks',

            'admin.tips.index' => 'tips',
            'admin.tips.fee' => 'tips',
            'admin.tips.deposit' => 'tips',

            'admin.medals.index' => 'medals',
            'admin.medals.store' => 'medals',
            'admin.medals.update' => 'medals',
            'admin.medals.assign' => 'medals',
            'admin.medals.revoke' => 'medals',

            'admin.awards.index' => 'awards',
            'admin.awards.categories.store' => 'awards',
            'admin.awards.store' => 'awards',
            'admin.awards.update' => 'awards',
            'admin.awards.grant' => 'awards',
            'admin.awards.revoke' => 'awards',

            'admin.vidu-ads.index' => 'vidu_ads',
            'admin.vidu-ads.settings' => 'vidu_ads',
            'admin.vidu-ads.creatives.store' => 'vidu_ads',
            'admin.vidu-ads.creatives.toggle' => 'vidu_ads',
            'admin.vidu-ads.creatives.destroy' => 'vidu_ads',
            'admin.vidu-ads.banners.store' => 'vidu_ads',
            'admin.vidu-ads.banners.update' => 'vidu_ads',
            'admin.vidu-ads.banners.destroy' => 'vidu_ads',
            'admin.vidu-ads.videos.bulk' => 'vidu_ads',
            'admin.vidu-ads.videos.update' => 'vidu_ads',
        ];

        return $map[$routeName] ?? null;
    }

    /**
     * Devuelve las pestañas permitidas para un usuario (admin = todas).
     */
    public static function tabsForUser(User $user): array
    {
        if ($user->isAdmin()) {
            return self::allTabKeys();
        }

        if (! $user->hasStaffRank()) {
            return [];
        }

        $configured = $user->rango?->admin_permissions;

        if (is_array($configured) && $configured !== []) {
            return array_values(array_intersect($configured, self::allTabKeys()));
        }

        return self::moderatorDefaults();
    }

    /**
     * Construye los ítems de navegación lateral filtrados por permisos del usuario.
     */
    public static function navForUser(User $user): array
    {
        $allowed = self::tabsForUser($user);
        $nav = [];

        foreach (self::definitions() as $key => $definition) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }

            $nav[] = [
                'key' => $key,
                'href' => $definition['href'],
                'label' => $definition['label'],
                'icon' => $definition['icon'],
                'exact' => $definition['exact'] ?? false,
                'group' => $definition['group'] ?? 'General',
            ];
        }

        return $nav;
    }

    /**
     * URL de la primera pestaña permitida (entrada al panel para staff sin dashboard).
     */
    public static function defaultHrefForUser(User $user): string
    {
        $nav = self::navForUser($user);

        return $nav[0]['href'] ?? '/admin/moderacion';
    }

    /**
     * Opciones agrupadas para el formulario de rangos staff.
     */
    public static function groupedOptionsForForm(): array
    {
        $groups = [];

        foreach (self::definitions() as $key => $definition) {
            $group = $definition['group'] ?? 'General';
            $groups[$group][] = [
                'key' => $key,
                'label' => $definition['label'],
            ];
        }

        return $groups;
    }
}
