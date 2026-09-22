<?php

namespace App\Http\Controllers\Api;

/**
 * API proxy de GIPHY: oculta la API key y expone búsqueda y tendencias al frontend.
 */

use App\Http\Controllers\Controller;
use App\Services\GiphyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiphyApiController extends Controller
{
    /**
     * Inyecta el servicio proxy de GIPHY para buscar GIFs sin exponer credenciales al cliente.
     */
    public function __construct(
        private readonly GiphyService $giphyService,
    ) {}

    /**
     * GET /api/giphy/status — indica si la integración con GIPHY está disponible.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'configured' => $this->giphyService->isConfigured(),
        ]);
    }

    /**
     * GET /api/giphy/trending — devuelve GIFs en tendencia.
     */
    public function trending(Request $request): JsonResponse
    {
        if (! $this->giphyService->isConfigured()) {
            return response()->json([
                'message' => 'GIPHY no está configurado en el servidor.',
                'data' => [],
            ], 503);
        }

        return response()->json([
            'data' => $this->giphyService->trending(
                limit: (int) $request->integer('limit', 24),
                offset: (int) $request->integer('offset', 0),
            ),
        ]);
    }

    /**
     * GET /api/giphy/search — busca GIFs por término.
     */
    public function search(Request $request): JsonResponse
    {
        if (! $this->giphyService->isConfigured()) {
            return response()->json([
                'message' => 'GIPHY no está configurado en el servidor.',
                'data' => [],
            ], 503);
        }

        return response()->json([
            'data' => $this->giphyService->search(
                query: (string) $request->string('q'),
                limit: (int) $request->integer('limit', 24),
                offset: (int) $request->integer('offset', 0),
            ),
        ]);
    }
}
