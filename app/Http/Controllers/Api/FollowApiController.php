<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de seguimiento entre usuarios: seguir y dejar de seguir por nombre de usuario.
 */

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowApiController extends Controller
{
    /**
     * Inyecta el servicio de seguimiento para gestionar relaciones entre perfiles de Gofio.
     */
    public function __construct(
        private readonly FollowService $followService,
    ) {}

    /**
     * POST /api/users/{username}/follow — responde con JSON del estado de seguimiento actualizado.
     */
    public function follow(Request $request, string $username): JsonResponse
    {
        $target = User::query()->where('username', $username)->firstOrFail();

        return response()->json([
            'data' => $this->followService->follow($request->user(), $target),
        ]);
    }

    /**
     * DELETE /api/users/{username}/follow — responde con JSON confirmando la cancelación del seguimiento.
     */
    public function unfollow(Request $request, string $username): JsonResponse
    {
        $target = User::query()->where('username', $username)->firstOrFail();

        return response()->json([
            'data' => $this->followService->unfollow($request->user(), $target),
        ]);
    }
}
