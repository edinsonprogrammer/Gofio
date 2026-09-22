<?php

/**
 * Controlador admin de paquetes de iconos: listado, sincronización y biblioteca de assets.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IconPack;
use App\Services\IconPackLibraryService;
use App\Services\IconPackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IconPackAdminController extends Controller
{
    public function __construct(
        private readonly IconPackService $iconPackService,
        private readonly IconPackLibraryService $iconPackLibraryService,
    ) {}

    /**
     * GET /admin/iconos — responde con la vista Inertia Admin/IconPacks/Index y el catálogo de paquetes.
     */
    public function index(): Response
    {
        $sync = $this->iconPackService->syncFolderPacks();

        // Renderiza el gestor de paquetes de iconos
        return Inertia::render('Admin/IconPacks/Index', [
            'iconPacks' => $this->iconPackService->listForAdmin()->map(fn (IconPack $pack) => [
                'id' => $pack->id,
                'name' => $pack->name,
                'slug' => $pack->slug,
                'source' => $pack->source,
                'description' => $pack->description,
                'author' => $pack->author,
                'version' => $pack->version,
                'is_active' => $pack->is_active,
                'ranks' => $pack->rankMappings(),
                'medals' => $pack->medalMappings(),
            ]),
            'iconPacksPath' => $sync['path'],
            'lastSync' => $sync,
        ]);
    }

    /**
     * POST /admin/iconos/sincronizar — importa paquetes desde carpetas, limpia caché y redirige de vuelta.
     */
    public function sync(): RedirectResponse
    {
        $result = $this->iconPackService->syncFolderPacks();

        if ($result['total'] === 0) {
            return back()->with('error', "No se encontraron paquetes de iconos válidos en {$result['path']}.");
        }

        $parts = [];
        if ($result['created']) {
            $parts[] = 'instalados: '.implode(', ', $result['created']);
        }
        if ($result['updated']) {
            $parts[] = 'actualizados: '.implode(', ', $result['updated']);
        }

        $this->iconPackLibraryService->flushCache();

        return back()->with('success', 'Paquetes de iconos sincronizados ('.implode(' · ', $parts).').');
    }

    /**
     * GET /admin/iconos/biblioteca — responde con JSON del catálogo de iconos disponibles (con opción de refresco).
     */
    public function library(Request $request): JsonResponse
    {
        $fresh = $request->boolean('fresh');

        return response()->json([
            'data' => $this->iconPackLibraryService->library($fresh),
        ]);
    }
}
