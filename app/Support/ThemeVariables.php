<?php

/**
 * Utilidad con variables CSS por defecto del tema Menta Tecnológico de Gofio.
 * Cubre colores, tipografía, radio, sombras y espaciado estructural.
 */

namespace App\Support;

class ThemeVariables
{

    /**
     * Devuelve el mapa completo de variables CSS por defecto.
     * Separadas en grupos: colores, tipografía, forma, sombras, estructura.
     */
    public static function defaults(): array
    {
        return [
            // ─── Colores principales ────────────────────────────────────────
            '--bg-principal'                  => '#F1F5F9',
            '--text-principal'                => '#0F172A',
            '--color-brand'                   => '#0D9488',
            '--color-surface'                 => '#FFFFFF',
            '--color-border'                  => '#CBD5E1',
            '--color-muted'                   => '#64748B',
            '--color-link'                    => '#0F766E',
            '--color-topbar-dark'             => '#115E59',
            '--color-panel'                   => '#F0FDFA',
            '--color-brand-hover'             => '#0F766E',
            '--color-input-border'            => '#94A3B8',
            '--color-btn-secondary-bg'        => '#F0FDFA',
            '--color-btn-secondary-border'    => '#99F6E4',
            '--color-btn-secondary-text'      => '#134E4A',
            '--color-topbar-gradient-from'    => '#0F766E',
            '--color-topbar-gradient-to'      => '#14B8A6',
            '--color-accent'                  => '#2DD4BF',
            '--color-accent-soft'             => '#CCFBF1',

            // ─── Colores de estado ──────────────────────────────────────────
            '--color-success'                 => '#16A34A',
            '--color-success-soft'            => '#DCFCE7',
            '--color-error'                   => '#DC2626',
            '--color-error-soft'              => '#FEE2E2',
            '--color-warning'                 => '#D97706',
            '--color-warning-soft'            => '#FEF3C7',

            // ─── Tipografía ─────────────────────────────────────────────────
            '--font-sans'                     => "'Inter', system-ui, -apple-system, sans-serif",
            '--font-size-base'                => '15px',
            '--font-weight-normal'            => '400',
            '--font-weight-semibold'          => '600',
            '--line-height-base'              => '1.55',

            // ─── Forma y radio ──────────────────────────────────────────────
            '--radius-sm'                     => '4px',
            '--radius-md'                     => '8px',
            '--radius-lg'                     => '12px',
            '--radius-xl'                     => '16px',
            '--radius-full'                   => '9999px',

            // ─── Sombras ────────────────────────────────────────────────────
            '--shadow-card'                   => '0 1px 3px rgba(0,0,0,0.08)',
            '--shadow-elevated'               => '0 4px 14px rgba(0,0,0,0.10)',
            '--shadow-topbar'                 => '0 2px 8px rgba(0,0,0,0.15)',

            // ─── Estructura y espaciado ─────────────────────────────────────
            '--topbar-height'                 => '52px',
            '--sidebar-left-width'            => '200px',
            '--sidebar-right-width'           => '240px',
            '--sidenav-width'                 => '240px',
            '--content-max-width'             => '1152px',
            '--content-gap'                   => '1rem',
        ];
    }

    /**
     * Devuelve la lista de nombres de variables CSS disponibles.
     */
    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    /**
     * Combina valores por defecto con sobrescrituras del usuario, descartando vacíos.
     */
    public static function merge(array $overrides): array
    {
        return array_merge(self::defaults(), array_filter($overrides, fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Devuelve los grupos de variables para la UI de administración (incluye tipografía y estructura).
     */
    public static function groups(): array
    {
        return array_merge(self::colorGroups(), [
            'Tipografía'           => [
                '--font-sans', '--font-size-base', '--font-weight-normal', '--font-weight-semibold', '--line-height-base',
            ],
            'Forma y radio'        => [
                '--radius-sm', '--radius-md', '--radius-lg', '--radius-xl', '--radius-full',
            ],
            'Sombras'              => [
                '--shadow-card', '--shadow-elevated', '--shadow-topbar',
            ],
            'Estructura'           => [
                '--topbar-height', '--sidebar-left-width', '--sidebar-right-width',
                '--sidenav-width', '--content-max-width', '--content-gap',
            ],
        ]);
    }

    /**
     * Grupos solo de color para combinaciones creadas en el panel (sin alterar layout ni estructura).
     */
    public static function colorGroups(): array
    {
        return [
            'Fondo y superficies'  => [
                '--bg-principal', '--color-surface', '--color-panel', '--color-border', '--color-muted',
            ],
            'Texto y enlaces'      => [
                '--text-principal', '--color-link',
            ],
            'Marca e interacción'  => [
                '--color-brand', '--color-brand-hover', '--color-accent', '--color-accent-soft',
            ],
            'Barra superior'       => [
                '--color-topbar-gradient-from', '--color-topbar-gradient-to', '--color-topbar-dark',
            ],
            'Formularios y botones secundarios' => [
                '--color-input-border',
                '--color-btn-secondary-bg', '--color-btn-secondary-border', '--color-btn-secondary-text',
            ],
            'Estados del sistema'  => [
                '--color-success', '--color-success-soft',
                '--color-error', '--color-error-soft',
                '--color-warning', '--color-warning-soft',
            ],
        ];
    }

    /**
     * Describe en qué sector de la interfaz se aplica cada variable de color.
     */
    public static function sectorLabels(): array
    {
        return [
            '--bg-principal'               => 'Fondo general de la página y márgenes externos',
            '--color-surface'              => 'Tarjetas, cajas de contenido y publicaciones',
            '--color-panel'                => 'Sidebars, paneles laterales y fondos suaves',
            '--color-border'               => 'Bordes de cajas, inputs y separadores',
            '--color-muted'                => 'Texto secundario, metadatos y placeholders',
            '--text-principal'             => 'Títulos, cuerpo de texto y navegación principal',
            '--color-link'                 => 'Enlaces, acciones destacadas y botones de texto',
            '--color-brand'                => 'Botones primarios, badges activos e iconos de marca',
            '--color-brand-hover'          => 'Hover de botones primarios y enlaces interactivos',
            '--color-accent'               => 'Detalles decorativos, iconos del topbar y acentos',
            '--color-accent-soft'          => 'Fondos suaves de destacados y estados hover en paneles',
            '--color-topbar-gradient-from' => 'Inicio del degradado de la barra superior',
            '--color-topbar-gradient-to'   => 'Final del degradado de la barra superior',
            '--color-topbar-dark'          => 'Sombras y variantes oscuras del encabezado',
            '--color-input-border'         => 'Bordes de campos de texto y selectores',
            '--color-btn-secondary-bg'     => 'Fondo de botones secundarios y acciones neutras',
            '--color-btn-secondary-border' => 'Borde de botones secundarios',
            '--color-btn-secondary-text'   => 'Texto dentro de botones secundarios',
            '--color-success'              => 'Mensajes de éxito y confirmaciones',
            '--color-success-soft'         => 'Fondo suave de alertas de éxito',
            '--color-error'                => 'Errores de validación y acciones destructivas',
            '--color-error-soft'           => 'Fondo suave de alertas de error',
            '--color-warning'              => 'Advertencias y avisos moderados',
            '--color-warning-soft'         => 'Fondo suave de alertas de advertencia',
        ];
    }
}
