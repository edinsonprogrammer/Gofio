<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de beneficios Creator Plus: tickets prioritarios y visitas al perfil.
 */

use App\Http\Controllers\Controller;
use App\Services\ProfileVisitService;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorPlusApiController extends Controller
{
    /**
     * Inyecta servicios exclusivos de Creator Plus: soporte prioritario y analítica de visitas al perfil.
     */
    public function __construct(
        private readonly SupportTicketService $supportTicketService,
        private readonly ProfileVisitService $profileVisitService,
    ) {}

    /**
     * POST /api/creator-plus/priority-ticket — valida asunto y cuerpo y responde con JSON del ticket creado.
     */
    public function priorityTicket(Request $request): JsonResponse
    {
        // Valida los campos del ticket de soporte prioritario
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $result = $this->supportTicketService->createCreatorPlusPriorityTicket(
            $request->user(),
            $data['subject'],
            $data['body'],
        );

        return response()->json($result);
    }

    /**
     * GET /api/creator-plus/profile-visits — responde con JSON del historial de visitas al perfil del usuario.
     */
    public function profileVisits(Request $request): JsonResponse
    {
        $visits = $this->profileVisitService->listForUser($request->user());

        return response()->json([
            'data' => $visits->through(fn ($visit) => [
                'id' => $visit->id,
                'visited_at' => $visit->visited_at?->toISOString(),
                'visitor' => [
                    'id' => $visit->visitor->id,
                    'username' => $visit->visitor->username,
                    'avatar_url' => $visit->visitor->avatar_url,
                    'tipo_verificacion' => $visit->visitor->displayVerificationTipo(),
                ],
            ])->items(),
        ]);
    }
}
