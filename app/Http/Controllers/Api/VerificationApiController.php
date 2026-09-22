<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de verificación de identidad: consulta de estado y envío de solicitudes con documento.
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitVerificationRequest;
use App\Services\SubscriptionService;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;

class VerificationApiController extends Controller
{
    /**
     * Inyecta el servicio de verificación para consultar estado y procesar solicitudes con documento.
     */
    public function __construct(
        private readonly VerificationService $verificationService,
    ) {}

    /**
     * GET /api/verification/status — responde con JSON del estado de verificación del usuario autenticado.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'data' => $this->verificationService->getStatus(auth()->user()),
        ]);
    }

    /**
     * POST /api/verification/submit — valida el formulario con documento y responde con JSON de la solicitud creada (201).
     */
    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        $verification = $this->verificationService->submit(
            auth()->user(),
            $request->validated(),
            $request->file('document'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada. Un administrador la revisará pronto.',
            'data' => [
                'id' => $verification->id,
                'status' => $verification->status,
            ],
        ], 201);
    }
}
