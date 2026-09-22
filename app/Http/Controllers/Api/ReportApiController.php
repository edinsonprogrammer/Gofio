<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de denuncias: catálogo de motivos y envío de reportes de contenido o usuarios.
 */

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\ReportService;
use App\Support\ReportReasons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportApiController extends Controller
{
    /**
     * Inyecta el servicio de denuncias para registrar reportes de usuarios, posts y comentarios.
     */
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * GET /api/reports/reasons — responde con JSON del catálogo de motivos de denuncia disponibles.
     */
    public function reasons(): JsonResponse
    {
        return response()->json([
            'data' => ReportReasons::all(),
        ]);
    }

    /**
     * POST /api/reports — valida el reporte y responde con JSON de confirmación del envío.
     */
    public function store(Request $request): JsonResponse
    {
        // Valida tipo, identificador, motivo y detalles opcionales
        $data = $request->validate([
            'type' => ['required', Rule::in(['user', 'post', 'comment'])],
            'id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::in(ReportReasons::keys())],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $modelClass = match ($data['type']) {
            'user' => User::class,
            'post' => Post::class,
            'comment' => Comment::class,
        };

        $this->reportService->submit(
            $request->user(),
            $modelClass,
            (int) $data['id'],
            $data['reason'],
            $data['details'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Denuncia enviada. El equipo de moderación la revisará pronto.',
        ]);
    }
}
