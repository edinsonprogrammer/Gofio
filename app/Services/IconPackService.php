<?php

namespace App\Services;

/**
 * Sincroniza packs de iconos desde carpetas, administra listados y resuelve mappings activos por usuario.
 */

use App\Models\IconPack;
use App\Models\User;
use App\Repositories\IconPackRepository;
use Illuminate\Support\Collection;

class IconPackService
{
    /**
     * Inyecta repositorio, escáner, servicio de temas y biblioteca de iconos.
     */
    public function __construct(
        private readonly IconPackRepository $iconPackRepository,
        private readonly IconPackScanner $iconPackScanner,
        private readonly ThemeService $themeService,
        private readonly IconPackLibraryService $iconPackLibraryService,
    ) {}

    /**
     * Importa o actualiza packs desde el filesystem según manifiestos y limpia caché de biblioteca.
     */
    public function syncFolderPacks(): array
    {
        $found = $this->iconPackScanner->scan();

        $created = [];
        $updated = [];

        foreach ($found as $entry) {
            $manifest = $entry['manifest'];

            [$pack, $wasCreated] = $this->iconPackRepository->upsertFolderPack(
                slug: $manifest->slug,
                attributes: [
                    'name' => $manifest->name,
                    'source' => 'folder',
                    'description' => $manifest->description,
                    'author' => $manifest->author,
                    'version' => $manifest->version,
                    'mappings' => [
                        'ranks' => $manifest->ranks,
                        'medals' => $manifest->medals,
                    ],
                ],
            );

            $wasCreated ? $created[] = $pack->name : $updated[] = $pack->name;
        }

        $this->iconPackLibraryService->flushCache();

        return [
            'path' => $this->iconPackScanner->iconPacksPath(),
            'created' => $created,
            'updated' => $updated,
            'total' => count($found),
        ];
    }

    /**
     * Lista todos los icon packs para el panel de administración.
     */
    public function listForAdmin(): Collection
    {
        return $this->iconPackRepository->getAllForAdmin();
    }

    /**
     * Devuelve mappings de rangos y medallas del pack asociado al tema activo del usuario.
     */
    public function activeMappingsForUser(?User $user): ?array
    {
        $theme = $this->themeService->resolveForUser($user);

        if (! $theme->icon_pack_slug) {
            return null;
        }

        $pack = $this->iconPackRepository->findBySlug($theme->icon_pack_slug);

        if (! $pack) {
            return null;
        }

        return [
            'ranks' => $pack->rankMappings(),
            'medals' => $pack->medalMappings(),
        ];
    }
}
