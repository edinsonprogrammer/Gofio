<?php

namespace App\Services;

/**
 * Escanea carpetas de temas en disco y extrae manifiestos JSON junto con CSS personalizado.
 */

use App\Support\ThemeManifest;
use Illuminate\Support\Facades\File;

class ThemeFolderScanner
{
    private readonly string $themesPath;

    /**
     * Define la ruta raíz donde se encuentran las carpetas de temas instalables.
     */
    public function __construct(?string $themesPath = null)
    {
        $this->themesPath = $themesPath ?? base_path('themes');
    }

    /**
     * Recorre subcarpetas con theme.json válido y devuelve manifiesto, CSS y nombre de carpeta.
     */
    public function scan(): array
    {
        if (! File::isDirectory($this->themesPath)) {
            return [];
        }

        $results = [];

        foreach (File::directories($this->themesPath) as $folder) {
            $manifestPath = $folder.DIRECTORY_SEPARATOR.'theme.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $decoded = json_decode(File::get($manifestPath), true);

            if (! is_array($decoded)) {
                continue;
            }

            $manifest = ThemeManifest::fromArray($decoded);

            if (! $manifest) {
                continue;
            }

            $cssPath = $folder.DIRECTORY_SEPARATOR.'custom.css';

            $results[] = [
                'manifest' => $manifest,
                'custom_css' => File::exists($cssPath) ? File::get($cssPath) : null,
                'folder' => basename($folder),
            ];
        }

        return $results;
    }

    /**
     * Expone la ruta absoluta configurada para el directorio de temas.
     */
    public function themesPath(): string
    {
        return $this->themesPath;
    }
}
