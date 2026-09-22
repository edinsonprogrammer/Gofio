<?php

namespace App\Services;

/**
 * Escanea el directorio de icon-packs y lee manifiestos JSON de cada carpeta.
 */

use App\Support\IconPackManifest;
use Illuminate\Support\Facades\File;

class IconPackScanner
{
    private readonly string $iconPacksPath;

    /**
     * Configura la ruta base de icon packs (por defecto icon-packs/ en la raíz del proyecto).
     */
    public function __construct(?string $iconPacksPath = null)
    {
        $this->iconPacksPath = $iconPacksPath ?? base_path('icon-packs');
    }

    /**
     * Recorre subcarpetas con icon-pack.json válido y devuelve los manifiestos parseados.
     */
    public function scan(): array
    {
        if (! File::isDirectory($this->iconPacksPath)) {
            return [];
        }

        $results = [];

        foreach (File::directories($this->iconPacksPath) as $folder) {
            $manifestPath = $folder.DIRECTORY_SEPARATOR.'icon-pack.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $decoded = json_decode(File::get($manifestPath), true);

            if (! is_array($decoded)) {
                continue;
            }

            $manifest = IconPackManifest::fromArray($decoded);

            if (! $manifest) {
                continue;
            }

            $results[] = ['manifest' => $manifest];
        }

        return $results;
    }

    /**
     * Devuelve la ruta absoluta configurada del directorio de icon packs.
     */
    public function iconPacksPath(): string
    {
        return $this->iconPacksPath;
    }
}
