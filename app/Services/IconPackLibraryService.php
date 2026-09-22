<?php

namespace App\Services;

/**
 * Construye y cachea el catálogo de iconos disponibles en los packs del filesystem.
 */

use App\Support\IconPackManifest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class IconPackLibraryService
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'];

    private const SKIP_FILES = [
        'icon-pack.json',
        'indice-classic.json',
        'indice-graphite.json',
        'indice-slab.json',
        'attribution.md',
        '.gitkeep',
        'readme.md',
    ];

    private const SKIP_PATH_PREFIXES = [
        '_sources/',
    ];

    /**
     * Inyecta el escáner de carpetas de icon packs.
     */
    public function __construct(
        private readonly IconPackScanner $iconPackScanner,
    ) {}

    /**
     * Devuelve la biblioteca de iconos desde caché o la reconstruye si se solicita fresh.
     */
    public function library(bool $fresh = false): array
    {
        $cacheKey = 'gofio.icon-pack.library';

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->buildLibrary();
        });
    }

    /**
     * Invalida la caché de la biblioteca de iconos.
     */
    public function flushCache(): void
    {
        Cache::forget('gofio.icon-pack.library');
    }

    /**
     * Escanea carpetas de packs, lee manifiestos y agrega entradas Font Awesome e imágenes.
     */
    private function buildLibrary(): array
    {
        $packs = [];
        $basePath = $this->iconPackScanner->iconPacksPath();

        if (! File::isDirectory($basePath)) {
            return ['packs' => []];
        }

        foreach (File::directories($basePath) as $folder) {
            $manifestPath = $folder.DIRECTORY_SEPARATOR.'icon-pack.json';
            $manifest = null;

            if (File::exists($manifestPath)) {
                $decoded = json_decode(File::get($manifestPath), true);
                if (is_array($decoded)) {
                    $manifest = IconPackManifest::fromArray($decoded);
                }
            }

            $slug = $manifest?->slug ?? basename($folder);

            if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
                continue;
            }

            $icons = [];

            if ($manifest) {
                $icons = array_merge(
                    $icons,
                    $this->fontawesomeEntries($manifest->ranks, $slug),
                    $this->fontawesomeEntries($manifest->medals, $slug),
                );
            }

            $icons = array_merge($icons, $this->imageEntries($folder, $slug));
            $icons = $this->uniqueIcons($icons);

            if ($icons === [] && ! $manifest) {
                continue;
            }

            $packs[] = [
                'slug' => $slug,
                'name' => $manifest?->name ?? ucfirst(str_replace('-', ' ', $slug)),
                'description' => $manifest?->description,
                'groups' => $this->groupSummary($icons),
                'icons' => $icons,
            ];
        }

        usort($packs, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return ['packs' => $packs];
    }

    /**
     * Resuelve la ruta absoluta segura de un asset de imagen dentro de un pack.
     */
    public function resolveAssetPath(string $packSlug, string $relativePath): ?string
    {
        if (! preg_match('/^[a-z0-9\-]+$/', $packSlug)) {
            return null;
        }

        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim($relativePath, '/');

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $fullPath = $this->iconPackScanner->iconPacksPath()
            .DIRECTORY_SEPARATOR.$packSlug
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        $realBase = realpath($this->iconPackScanner->iconPacksPath().DIRECTORY_SEPARATOR.$packSlug);
        $realFile = realpath($fullPath);

        if (! $realBase || ! $realFile || ! str_starts_with($realFile, $realBase)) {
            return null;
        }

        if (! File::isFile($realFile)) {
            return null;
        }

        $extension = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));

        if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        return $realFile;
    }

    /**
     * Convierte el mapa de manifiesto (ranks/medals) en entradas tipo Font Awesome.
     */
    private function fontawesomeEntries(array $map, string $packSlug): array
    {
        $entries = [];

        foreach ($map as $slug => $value) {
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            $entries[] = [
                'type' => 'fontawesome',
                'value' => $value,
                'label' => $slug,
                'pack' => $packSlug,
                'group' => 'fontawesome',
                'group_label' => 'Font Awesome (manifest)',
            ];
        }

        return $entries;
    }

    /**
     * Recorre archivos de imagen del pack y genera entradas con URL y metadatos de grupo.
     */
    private function imageEntries(string $folder, string $packSlug): array
    {
        $entries = [];

        foreach (File::allFiles($folder) as $file) {
            $basename = strtolower($file->getFilename());

            if (in_array($basename, self::SKIP_FILES, true)) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($folder) + 1));

            if ($relative === '' || in_array(strtolower(basename($relative)), self::SKIP_FILES, true)) {
                continue;
            }

            if ($this->shouldSkipRelativePath($relative)) {
                continue;
            }

            $value = 'pack:'.$packSlug.'/'.$relative;
            $group = $this->resolveGroup($relative);
            [$label, $groupLabel] = $this->resolveLabels($relative, $group);

            $entries[] = [
                'type' => 'image',
                'value' => $value,
                'label' => $label,
                'pack' => $packSlug,
                'group' => $group,
                'group_label' => $groupLabel,
                'url' => $this->assetUrl($packSlug, $relative),
                'relative' => $relative,
            ];
        }

        return $entries;
    }

    /**
     * Determina la clave de grupo según la ruta relativa del archivo de icono.
     */
    private function resolveGroup(string $relative): string
    {
        $normalized = str_replace('\\', '/', $relative);
        $dir = dirname($normalized);

        if ($dir === '.' || $dir === '') {
            return 'personalizados';
        }

        if (str_starts_with($normalized, 'imagenes/') || str_starts_with($normalized, 'images/')) {
            return 'imagenes';
        }

        return str_replace('\\', '/', $dir);
    }

    /**
     * Devuelve etiqueta del icono y etiqueta legible del grupo según convenciones del pack.
     */
    private function resolveLabels(string $relative, string $group): array
    {
        $filename = pathinfo($relative, PATHINFO_FILENAME);

        return match ($group) {
            'imagenes' => [$filename, 'Mis imágenes'],
            'personalizados' => [$filename, 'Personalizados (raíz)'],
            'classic/solid' => [$filename, 'Classic · Sólido'],
            'classic/regular' => [$filename, 'Classic · Regular'],
            'graphite/thin' => [$filename, 'Graphite · Thin'],
            'slab/regular' => [$filename, 'Slab · Regular'],
            'slab-press/regular' => [$filename, 'Slab Press · Regular'],
            'slab-duo/regular' => [$filename, 'Slab Duo · Regular'],
            'slab-press-duo/regular' => [$filename, 'Slab Press Duo · Regular'],
            default => [$filename, ucfirst(str_replace(['/', '-'], [' · ', ' '], $group))],
        };
    }

    /**
     * Genera la URL pública codificada para servir un asset del pack.
     */
    private function assetUrl(string $packSlug, string $relative): string
    {
        $encoded = implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $relative))));

        return '/icon-packs/assets/'.$packSlug.'/'.$encoded;
    }

    /**
     * Resume la cantidad de iconos por grupo con orden predefinido de presentación.
     */
    private function groupSummary(array $icons): array
    {
        $counts = [];

        foreach ($icons as $icon) {
            $key = (string) ($icon['group'] ?? 'otros');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $order = [
            'imagenes',
            'personalizados',
            'fontawesome',
            'graphite/thin',
            'slab/regular',
            'slab-press/regular',
            'slab-duo/regular',
            'slab-press-duo/regular',
            'classic/solid',
            'classic/regular',
        ];
        $groups = [];

        foreach ($order as $key) {
            if (! isset($counts[$key])) {
                continue;
            }

            $groups[] = [
                'key' => $key,
                'label' => $this->groupLabel($key),
                'count' => $counts[$key],
            ];

            unset($counts[$key]);
        }

        ksort($counts);

        foreach ($counts as $key => $count) {
            $groups[] = [
                'key' => $key,
                'label' => $this->groupLabel($key),
                'count' => $count,
            ];
        }

        return $groups;
    }

    /**
     * Traduce la clave interna de grupo a una etiqueta legible para la UI.
     */
    private function groupLabel(string $group): string
    {
        return match ($group) {
            'imagenes' => 'Mis imágenes',
            'personalizados' => 'Personalizados (raíz)',
            'fontawesome' => 'Font Awesome (manifest)',
            'graphite/thin' => 'Graphite · Thin',
            'slab/regular' => 'Slab · Regular',
            'slab-press/regular' => 'Slab Press · Regular',
            'slab-duo/regular' => 'Slab Duo · Regular',
            'slab-press-duo/regular' => 'Slab Press Duo · Regular',
            'classic/solid' => 'Classic · Sólido',
            'classic/regular' => 'Classic · Regular',
            default => ucfirst(str_replace(['/', '-'], [' · ', ' '], $group)),
        };
    }

    /**
     * Indica si la ruta relativa debe omitirse por prefijos excluidos (p. ej. fuentes).
     */
    private function shouldSkipRelativePath(string $relative): bool
    {
        $normalized = strtolower(str_replace('\\', '/', $relative));

        foreach (self::SKIP_PATH_PREFIXES as $prefix) {
            if (str_starts_with($normalized, strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Elimina entradas duplicadas por combinación type:value.
     */
    private function uniqueIcons(array $icons): array
    {
        $seen = [];
        $unique = [];

        foreach ($icons as $icon) {
            $key = ($icon['type'] ?? '').':'.($icon['value'] ?? '');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $icon;
        }

        return $unique;
    }
}
