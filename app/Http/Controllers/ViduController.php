<?php

/**
 * Controlador web de Vidu: sirve las páginas Inertia del feed y creación de videos.
 */

namespace App\Http\Controllers;

use App\Models\ViduVideo;
use App\Services\ViduService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ViduController extends Controller
{
    public function __construct(
        private readonly ViduService $viduService,
    ) {}

    /**
     * GET /vidu — renderiza el feed de videos con los primeros resultados.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $feed = $this->viduService->feed($user, 1, 8);

        return Inertia::render('Vidu/Index', [
            'initialVideos'  => $feed['videos'],
            'hasMore'        => $feed['has_more'],
        ]);
    }

    /**
     * GET /vidu/crear — muestra el formulario de subida de video.
     */
    public function crear(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Vidu/Crear', [
            'maxDuration' => $this->viduService->maxDurationSeconds($user),
            'maxSizeMb'   => (int) round($this->viduService->maxFileSizeKb($user) / 1024),
            'isCreator'   => $user->isCreatorPlus() || $user->tipo_verificacion !== null,
        ]);
    }

    /**
     * GET /vidu/{video} — muestra un video individual (para compartir).
     */
    public function show(Request $request, ViduVideo $video): Response
    {
        if ($video->status !== 'active' || $video->is_private) {
            abort(404);
        }

        $user = $request->user();
        $video->loadMissing([
            'user' => static fn ($q) => $q->with('rango'),
        ]);

        if ($user) {
            $video->setAttribute('liked_by_user', $video->likedByUsers()->where('user_id', $user->id)->exists());
            $video->setAttribute('saved_by_user', $video->savedByUsers()->where('user_id', $user->id)->exists());
        }

        return Inertia::render('Vidu/Show', [
            'video'   => $this->viduService->presentVideo($video, $user),
            'canLike' => auth()->check(),
        ]);
    }
}
