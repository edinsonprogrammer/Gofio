<?php

/**
 * Servicio Vidu: gestiona la subida, validación, feed y acciones de videos cortos.
 */

namespace App\Services;

use App\Models\User;
use App\Models\ViduVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ViduService
{
    /** Duración máxima en segundos para usuarios regulares. */
    private const MAX_SECONDS_DEFAULT = 60;

    /** Duración máxima en segundos para creadores verificados (Creator Plus o verificado). */
    private const MAX_SECONDS_CREATOR = 180;

    /** Tamaño máximo en KB para usuarios regulares (~100 MB). */
    private const MAX_KB_DEFAULT = 102400;

    /** Tamaño máximo en KB para creadores verificados (~300 MB). */
    private const MAX_KB_CREATOR = 307200;

    public function __construct(
        private readonly RankService $rankService,
        private readonly ViduAdService $viduAdService,
        private readonly KarmaRuleService $karmaRuleService,
    ) {}

    /**
     * Devuelve la duración máxima permitida según el tipo de usuario.
     */
    public function maxDurationSeconds(User $user): int
    {
        return $this->isVerifiedCreator($user)
            ? self::MAX_SECONDS_CREATOR
            : self::MAX_SECONDS_DEFAULT;
    }

    /**
     * Devuelve el tamaño máximo de archivo en KB según el tipo de usuario.
     */
    public function maxFileSizeKb(User $user): int
    {
        return $this->isVerifiedCreator($user)
            ? self::MAX_KB_CREATOR
            : self::MAX_KB_DEFAULT;
    }

    /**
     * Sube y registra un video Vidu tras validar duración y tamaño.
     *
     * @throws ValidationException
     */
    public function upload(User $user, UploadedFile $file, array $data): ViduVideo
    {
        $maxKb = $this->maxFileSizeKb($user);
        $maxSeconds = $this->maxDurationSeconds($user);

        // Valida tamaño del archivo como proxy de duración en el servidor
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'video' => ["El archivo supera el límite de {$maxKb} KB."],
            ]);
        }

        // Valida duración reportada por el cliente
        $durationSeconds = (int) ($data['duration_seconds'] ?? 0);

        if ($durationSeconds > $maxSeconds) {
            $minutes = $maxSeconds / 60;
            throw ValidationException::withMessages([
                'video' => ["El video supera los {$minutes} minutos permitidos para tu cuenta."],
            ]);
        }

        // Guarda el archivo en el disco público
        $path = $file->store('vidu/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        // Gestiona thumbnail opcional
        $thumbnailUrl = null;
        if (! empty($data['thumbnail_data_url'])) {
            $thumbnailUrl = $this->storeThumbnailFromDataUrl($data['thumbnail_data_url']);
        }

        $video = ViduVideo::create([
            'user_id'          => $user->id,
            'title'            => isset($data['title']) ? trim((string) $data['title']) : null,
            'description'      => isset($data['description']) ? trim((string) $data['description']) : null,
            'video_path'       => $path,
            'video_url'        => $url,
            'thumbnail_url'    => $thumbnailUrl,
            'duration_seconds' => $durationSeconds,
            'status'           => 'active',
        ]);

        // Karma por subir un Vidu Reel (regla vidu_uploaded)
        $this->karmaRuleService->evaluateViduUploaded($user, $video->id);

        return $video;
    }

    /**
     * Feed aleatorio paginado con estado de like/guardado para el usuario.
     */
    public function feed(User $user, int $page = 1, int $perPage = 8): array
    {
        // Semilla aleatoria por sesión para paginación consistente
        $seed = (int) session()->remember('vidu_feed_seed', static fn () => rand(1, 999999));

        $paginator = ViduVideo::public()
            ->with($this->authorEagerLoad())
            ->withExists([
                'likedByUsers as liked_by_user' => static fn ($q) => $q->where('user_id', $user->id),
                'savedByUsers as saved_by_user' => static fn ($q) => $q->where('user_id', $user->id),
            ])
            ->orderByRaw('RAND(?)', [$seed])
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'videos'       => array_values(array_map(
                fn ($v) => $this->formatVideo($v, $user),
                $paginator->items(),
            )),
            'has_more'     => $paginator->hasMorePages(),
            'next_page'    => $page + 1,
            'current_page' => $page,
            'total'        => $paginator->total(),
        ];
    }

    /**
     * Videos guardados por el usuario autenticado.
     */
    public function savedFeed(User $user, int $page = 1, int $perPage = 12): array
    {
        $paginator = ViduVideo::public()
            ->whereHas('savedByUsers', fn ($q) => $q->where('user_id', $user->id))
            ->with($this->authorEagerLoad())
            ->withExists([
                'likedByUsers as liked_by_user' => static fn ($q) => $q->where('user_id', $user->id),
                'savedByUsers as saved_by_user' => static fn ($q) => $q->where('user_id', $user->id),
            ])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'videos'    => array_values(array_map(fn ($v) => $this->formatVideo($v, $user), $paginator->items())),
            'has_more'  => $paginator->hasMorePages(),
            'next_page' => $page + 1,
        ];
    }

    /**
     * Videos del propio usuario autenticado.
     */
    public function myVideos(User $user, int $page = 1, int $perPage = 12): array
    {
        $paginator = ViduVideo::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'banned')
            ->with($this->authorEagerLoad())
            ->withExists([
                'likedByUsers as liked_by_user' => static fn ($q) => $q->where('user_id', $user->id),
                'savedByUsers as saved_by_user' => static fn ($q) => $q->where('user_id', $user->id),
            ])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'videos'    => array_values(array_map(fn ($v) => $this->formatVideo($v, $user), $paginator->items())),
            'has_more'  => $paginator->hasMorePages(),
            'next_page' => $page + 1,
        ];
    }

    /**
     * Alterna el like de un usuario sobre un video; actualiza el contador.
     * Al dar like, evalúa karma para el autor por vidu_liked y vidu_popular.
     */
    public function toggleLike(User $user, ViduVideo $video): array
    {
        $liked = $video->likedByUsers()->where('user_id', $user->id)->exists();

        if ($liked) {
            $video->likedByUsers()->detach($user->id);
            $video->decrement('likes_count');
        } else {
            $video->likedByUsers()->syncWithoutDetaching([$user->id]);
            $video->increment('likes_count');

            // Solo otorga karma si el like es de otra persona, no del propio autor
            if ($video->user_id !== $user->id) {
                $video->loadMissing('user');
                $newLikesCount = $video->likes_count + 1;

                // Karma por recibir el primer like en este Vidu (regla vidu_liked)
                $this->karmaRuleService->evaluateViduLiked($video->user, $video->id);

                // Karma por Vidu popular si supera el umbral de likes (regla vidu_popular)
                $this->karmaRuleService->evaluateViduPopular($video->user, $video->id, $newLikesCount);
            }
        }

        $video->refresh();

        return ['liked' => ! $liked, 'likes_count' => $video->likes_count];
    }

    /**
     * Alterna el guardado de un video; actualiza el contador.
     */
    public function toggleSave(User $user, ViduVideo $video): array
    {
        $saved = $video->savedByUsers()->where('user_id', $user->id)->exists();

        if ($saved) {
            $video->savedByUsers()->detach($user->id);
            $video->decrement('saves_count');
        } else {
            $video->savedByUsers()->syncWithoutDetaching([$user->id]);
            $video->increment('saves_count');
        }

        $video->refresh();

        return ['saved' => ! $saved, 'saves_count' => $video->saves_count];
    }

    /**
     * Registra una visualización (debounced: ignora visitas repetidas en la misma sesión).
     */
    public function recordView(User $user, ViduVideo $video): void
    {
        $key = "vidu_viewed_{$user->id}_{$video->id}";

        if (session()->has($key)) {
            return;
        }

        $video->increment('views_count');
        session()->put($key, true);
    }

    /**
     * Elimina un video del usuario autenticado y su archivo del disco.
     */
    public function delete(User $user, ViduVideo $video): void
    {
        if ($video->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'No tienes permiso para eliminar este video.');
        }

        if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
            Storage::disk('public')->delete($video->video_path);
        }

        $video->delete();
    }

    /**
     * Verifica si un usuario tiene acceso ampliado (Creator Plus o verificado).
     */
    private function isVerifiedCreator(User $user): bool
    {
        return $user->isCreatorPlus() || $user->tipo_verificacion !== null;
    }

    /**
     * Guarda un thumbnail codificado en base64 y devuelve su URL pública.
     */
    private function storeThumbnailFromDataUrl(string $dataUrl): ?string
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);

        if (! in_array($extension, ['jpeg', 'jpg', 'png', 'webp'], true)) {
            return null;
        }

        $data = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));

        if ($data === false || strlen($data) > 5 * 1024 * 1024) {
            return null;
        }

        $path = 'vidu/thumbs/'.date('Y/m').'/'.uniqid('t_', true).'.'.$extension;
        Storage::disk('public')->put($path, $data);

        return Storage::disk('public')->url($path);
    }

    /**
     * Serializa un video para la vista pública o el API (Inertia / JSON).
     */
    public function presentVideo(ViduVideo $video, ?User $viewer = null): array
    {
        $video->loadMissing($this->authorEagerLoad());

        return $this->formatVideo($video, $viewer);
    }

    /**
     * Carga eager del autor con su rango para listados Vidu.
     */
    private function authorEagerLoad(): array
    {
        return [
            'user' => static function ($query) {
                $query->select(
                    'id',
                    'username',
                    'avatar_url',
                    'tipo_verificacion',
                    'creator_plus_expires_at',
                    'rango_id',
                )->with('rango');
            },
        ];
    }

    /**
     * Normaliza un registro ViduVideo para la respuesta JSON del API.
     */
    private function formatVideo(ViduVideo $video, ?User $currentUser = null): array
    {
        $u = $video->user;

        return [
            'id'              => $video->id,
            'video_url'       => $video->video_url,
            'thumbnail_url'   => $video->thumbnail_url,
            'duration_seconds'=> $video->duration_seconds,
            'title'           => $video->title,
            'description'     => $video->description,
            'likes_count'     => $video->likes_count,
            'saves_count'     => $video->saves_count,
            'views_count'     => $video->views_count,
            'liked_by_user'   => (bool) ($video->liked_by_user ?? false),
            'saved_by_user'   => (bool) ($video->saved_by_user ?? false),
            'created_at'      => $video->created_at?->diffForHumans(),
            'user'            => $u ? [
                'id'                 => $u->id,
                'username'           => $u->username,
                'avatar_url'         => $u->avatar_url,
                'tipo_verificacion'  => $u->tipo_verificacion,
                'is_creator_plus'    => $u->isCreatorPlus(),
                'rango'              => $this->rankService->formatRango($u->rango),
            ] : null,
            'can_delete'      => $currentUser !== null
                && ($video->user_id === $currentUser->id || $currentUser->isAdmin()),
            // Pasa el espectador actual para aplicar viewer_mode (ej. VIP sin anuncios).
            'ad_break'        => $this->viduAdService->resolveAdBreak($video, $currentUser),
            'sidebar_banner'  => $this->viduAdService->resolveSidebarBanner($video),
        ];
    }
}
