<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de comentarios: gestión de votos en comentarios individuales.
 */

use App\Http\Controllers\Controller;
use App\Repositories\CommentRepository;
use App\Services\VoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentApiController extends Controller
{
    /**
     * Inyecta repositorio de comentarios y servicio de votos para valorar respuestas en el feed.
     */
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly VoteService $voteService,
    ) {}

    /**
     * POST /api/comments/{comment}/vote — responde con JSON del resultado del voto o 404 si no existe.
     */
    public function vote(Request $request, int $comment): JsonResponse
    {
        $commentModel = $this->commentRepository->findById($comment);

        if (! $commentModel) {
            return response()->json(['message' => 'Comentario no encontrado.'], 404);
        }

        $result = $this->voteService->voteComment($request->user(), $commentModel);

        return response()->json($result);
    }
}
