<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de Vidu: feed, subida, likes, guardados y visualizaciones de videos cortos.
 */

use App\Http\Controllers\Controller;
use App\Models\ViduVideo;
use App\Services\ViduService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ViduApiController extends Controller
{
    /**
     * Inyecta el servicio de Vidu para gestionar el ciclo de vida de los videos cortos en Gofio.
     */
    public function __construct(
        private readonly ViduService $viduService,
    ) {}

    /**
     * GET /api/vidu/feed?page=N — devuelve videos aleatorios paginados.
     */
    public function feed(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->viduService->feed($request->user(), $page, 8);

        return response()->json($result);
    }

    /**
     * POST /api/vidu/upload — sube y valida un video corto.
     */
    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'video'               => ['required', 'file', 'mimes:mp4,webm,mov,avi,mkv,m4v,3gp', 'max:'.$this->viduService->maxFileSizeKb($user)],
            'title'               => ['nullable', 'string', 'max:150'],
            'description'         => ['nullable', 'string', 'max:500'],
            'duration_seconds'    => ['required', 'integer', 'min:1', 'max:'.$this->viduService->maxDurationSeconds($user)],
            'thumbnail_data_url'  => ['nullable', 'string', 'max:5242880'],
        ]);

        $video = $this->viduService->upload(
            $user,
            $request->file('video'),
            $request->only(['title', 'description', 'duration_seconds', 'thumbnail_data_url']),
        );

        $video->loadMissing('user');

        return response()->json([
            'success' => true,
            'video'   => [
                'id'        => $video->id,
                'video_url' => $video->video_url,
                'title'     => $video->title,
            ],
        ], 201);
    }

    /**
     * POST /api/vidu/{video}/like — alterna el like del usuario sobre el video.
     */
    public function like(Request $request, ViduVideo $video): JsonResponse
    {
        if ($video->status !== 'active') {
            return response()->json(['error' => 'Video no disponible.'], 422);
        }

        $result = $this->viduService->toggleLike($request->user(), $video);

        return response()->json($result);
    }

    /**
     * POST /api/vidu/{video}/save — alterna el guardado del video.
     */
    public function save(Request $request, ViduVideo $video): JsonResponse
    {
        if ($video->status !== 'active') {
            return response()->json(['error' => 'Video no disponible.'], 422);
        }

        $result = $this->viduService->toggleSave($request->user(), $video);

        return response()->json($result);
    }

    /**
     * POST /api/vidu/{video}/view — registra una reproducción (debounced por sesión).
     */
    public function view(Request $request, ViduVideo $video): JsonResponse
    {
        if ($video->status === 'active') {
            $this->viduService->recordView($request->user(), $video);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/vidu/saved?page=N — videos guardados por el usuario.
     */
    public function saved(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->viduService->savedFeed($request->user(), $page);

        return response()->json($result);
    }

    /**
     * GET /api/vidu/mine?page=N — videos propios del usuario.
     */
    public function mine(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->viduService->myVideos($request->user(), $page);

        return response()->json($result);
    }

    /**
     * DELETE /api/vidu/{video} — elimina un video del usuario o por admin.
     */
    public function destroy(Request $request, ViduVideo $video): JsonResponse
    {
        $this->viduService->delete($request->user(), $video);

        return response()->json(['success' => true]);
    }
}
