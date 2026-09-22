<?php

namespace App\Http\Controllers\Api;

/**
 * API REST del monedero virtual: resumen de saldo y envío de propinas a publicaciones.
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\SendTipRequest;
use App\Repositories\PostRepository;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    /**
     * Inyecta el monedero virtual y el repositorio de posts para propinas y consulta de saldo.
     */
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PostRepository $postRepository,
    ) {}

    /**
     * GET /api/wallet — responde con JSON del resumen del monedero del usuario autenticado.
     */
    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->walletService->getWalletSummary($request->user()),
        ]);
    }

    /**
     * POST /api/posts/{post}/tip — valida el monto y responde con JSON del resultado de la propina.
     */
    public function sendTip(SendTipRequest $request, int $post): JsonResponse
    {
        $postModel = $this->postRepository->findById($post);

        if (! $postModel || $postModel->status !== 'published') {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        $result = $this->walletService->sendTip(
            $request->user(),
            $postModel,
            (float) $request->validated('amount')
        );

        return response()->json($result);
    }
}
