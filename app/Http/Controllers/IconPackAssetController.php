<?php

/**
 * Controlador que sirve archivos estáticos de paquetes de iconos con cabeceras de caché.
 */

namespace App\Http\Controllers;

use App\Services\IconPackLibraryService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IconPackAssetController extends Controller
{
    public function __construct(
        private readonly IconPackLibraryService $iconPackLibraryService,
    ) {}

    /**
     * GET /icon-packs/assets/{pack}/{path} — responde con el archivo binario del icono o 404 si no existe.
     */
    public function show(string $pack, string $path): BinaryFileResponse|Response
    {
        $resolved = $this->iconPackLibraryService->resolveAssetPath($pack, $path);

        if (! $resolved) {
            abort(404);
        }

        return response()->file($resolved, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
