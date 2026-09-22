<?php

/**
 * DTO que parsea y valida el archivo icon-pack.json de un paquete de iconos.
 */

namespace App\Support;

class IconPackManifest
{

    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $author,
        public readonly ?string $version,

        public readonly array $ranks,

        public readonly array $medals,
    ) {}

    /**
     * Construye un IconPackManifest validado desde un array icon-pack.json.
     */
    public static function fromArray(array $data): ?self
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($name === '' || $slug === '' || ! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return null;
        }

        return new self(
            name: mb_substr($name, 0, 80),
            slug: mb_substr($slug, 0, 80),
            description: isset($data['description']) ? mb_substr((string) $data['description'], 0, 255) : null,
            author: isset($data['author']) ? mb_substr((string) $data['author'], 0, 80) : null,
            version: isset($data['version']) ? mb_substr((string) $data['version'], 0, 20) : null,
            ranks: self::stringMap($data['ranks'] ?? null),
            medals: self::stringMap($data['medals'] ?? null),
        );
    }

    /**
     * Normaliza un mapa de claves a strings no vacíos.
     */
    private static function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = array_filter($value, fn ($v) => is_string($v) && $v !== '');

        return array_map('strval', $map);
    }
}
