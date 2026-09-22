<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de suscripción Creator Plus: consulta de estado y contratación por meses.
 */

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionApiController extends Controller
{
    /**
     * Inyecta el servicio de suscripción para consultar y contratar Creator Plus en Gofio.
     */
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * GET /api/subscription/status — responde con JSON del estado de suscripción del usuario autenticado.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => $this->subscriptionService->getStatus(auth()->user()),
        ]);
    }

    /**
     * POST /api/subscription/subscribe — valida los meses solicitados y responde con JSON del resultado.
     */
    public function subscribe(Request $request): JsonResponse
    {
        // Valida la duración de la suscripción en meses
        $data = $request->validate([
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $result = $this->subscriptionService->subscribe(
            $request->user(),
            (int) ($data['months'] ?? 1),
        );

        return response()->json($result);
    }
}
