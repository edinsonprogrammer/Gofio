<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de personalización visual: consulta y selección de temas para usuarios Creator Plus.
 */

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeApiController extends Controller
{
    /**
     * Inyecta el servicio de temas para consultar y aplicar apariencia personalizada en Creator Plus.
     */
    public function __construct(
        private readonly ThemeService $themeService,
    ) {}

    /**
     * GET /api/themes/status — responde con JSON del estado de apariencia o 403 si no tiene Creator Plus.
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->canCustomizeAppearance()) {
            abort(403, 'La personalización de apariencia requiere Creator Plus.');
        }

        return response()->json([
            'data' => $this->themeService->getAppearanceStatus($user),
        ]);
    }

    /**
     * POST /api/themes/select — valida el tema elegido y responde con JSON del resultado de la selección.
     */
    public function select(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user->canCustomizeAppearance()) {
            abort(403, 'La personalización de apariencia requiere Creator Plus.');
        }

        // Valida que el tema exista en la base de datos
        $request->validate([
            'theme_id' => ['required', 'integer', 'exists:themes,id'],
        ]);

        $result = $this->themeService->selectTheme($user, (int) $request->input('theme_id'));

        return response()->json($result);
    }
}
