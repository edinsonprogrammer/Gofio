<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de notificaciones: historial, marcado individual y marcado masivo como visto.
 */

use App\Http\Controllers\Controller;

use App\Services\NotificationService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



class NotificationApiController extends Controller

{

    /**
     * Inyecta el servicio de notificaciones para consultar historial y gestionar el estado de lectura.
     */
    public function __construct(

        private readonly NotificationService $notificationService,

    ) {}



    /**
     * GET /api/notifications — responde con JSON del historial y contador de notificaciones no leídas.
     */
    public function index(Request $request): JsonResponse

    {

        $user = $request->user();



        return response()->json([

            'data' => [

                'badge_count' => $this->notificationService->badgeCount($user),

                'notifications' => $this->notificationService->historyFor($user),

                'retention_days' => NotificationService::RETENTION_DAYS,

            ],

        ]);

    }



    /**
     * POST /api/notifications/{notification}/read — marca una notificación como leída y responde con el nuevo contador.
     */
    public function markRead(Request $request, int $notification): JsonResponse

    {

        $user = $request->user();

        $this->notificationService->markRead($user, $notification);



        return response()->json([

            'success' => true,

            'badge_count' => $this->notificationService->badgeCount($user),

        ]);

    }



    /**
     * POST /api/notifications/mark-seen — marca todas como vistas y responde con contador en cero.
     */
    public function markSeen(Request $request): JsonResponse

    {

        $user = $request->user();

        $this->notificationService->markAllSeen($user);



        return response()->json([

            'success' => true,

            'badge_count' => 0,

        ]);

    }

}


