<?php

/**
 * DTO que parsea y valida el archivo theme.json de un tema en carpeta.
 * Soporta colores, tipografía, layout_variant y logo personalizado.
 */

namespace App\Support;

class ThemeManifest
{
    /** Variantes de layout aceptadas. */
    public const LAYOUT_VARIANTS = ['default', 'sidebar-nav', 'minimal', 'compact', 'panel'];

    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $author,
        public readonly ?string $version,
        public readonly bool $isDefault,
        public readonly bool $requiresCreatorPlus,

        public readonly array $variables,
        public readonly ?string $iconPack,

        /** Variante estructural del layout de página. */
        public readonly string $layoutVariant,

        /** URL o ruta relativa al logo personalizado (ej. /themes/mi-tema/assets/logo.png). */
        public readonly ?string $logoUrl,
    ) {}

    /**
     * Construye un ThemeManifest validado desde un array theme.json.
     */
    public static function fromArray(array $data): ?self
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($name === '' || $slug === '' || ! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return null;
        }

        $variables = is_array($data['variables'] ?? null) ? $data['variables'] : [];
        $variables = array_filter($variables, fn ($v) => is_string($v) || is_numeric($v));

        // Valida que la variante de layout sea una de las aceptadas
        $layoutVariant = (string) ($data['layout_variant'] ?? 'default');
        if (! in_array($layoutVariant, self::LAYOUT_VARIANTS, true)) {
            $layoutVariant = 'default';
        }

        return new self(
            name:                 mb_substr($name, 0, 80),
            slug:                 mb_substr($slug, 0, 80),
            description:          isset($data['description']) ? mb_substr((string) $data['description'], 0, 255) : null,
            author:               isset($data['author']) ? mb_substr((string) $data['author'], 0, 80) : null,
            version:              isset($data['version']) ? mb_substr((string) $data['version'], 0, 20) : null,
            isDefault:            (bool) ($data['is_default'] ?? false),
            requiresCreatorPlus:  (bool) ($data['requires_creator_plus'] ?? false),
            variables:            array_map('strval', $variables),
            iconPack:             isset($data['icon_pack']) ? mb_substr((string) $data['icon_pack'], 0, 80) : null,
            layoutVariant:        $layoutVariant,
            logoUrl:              isset($data['logo_url']) ? mb_substr((string) $data['logo_url'], 0, 512) : null,
        );
    }
}
