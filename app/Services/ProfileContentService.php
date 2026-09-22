<?php

/**
 * Contenido público del perfil: listados de posts y videos Vidu con paginación segura.
 */

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\ViduVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProfileContentService
{
    /** Máximo de ítems en la vista previa del perfil. */
    public const PREVIEW_LIMIT = 10;

    /** Máximo de ítems por página en el historial completo. */
    public const PAGE_SIZE = 15;

    /**
     * Posts recientes publicados y visibles en el perfil (vista previa).
     */
    public function recentPosts(User $profileUser, int $limit = self::PREVIEW_LIMIT): Collection
    {
        return $this->postsQuery($profileUser)
            ->limit($limit)
            ->get()
            ->map(fn (Post $post) => $this->formatPostItem($post));
    }

    /**
     * Posts paginados del perfil para la página de historial completo.
     */
    public function paginatedPosts(User $profileUser, int $page = 1): LengthAwarePaginator
    {
        return $this->postsQuery($profileUser)
            ->paginate(self::PAGE_SIZE, ['*'], 'page', $page)
            ->through(fn (Post $post) => $this->formatPostItem($post));
    }

    /**
     * Total de posts públicos visibles en el perfil.
     */
    public function postsTotal(User $profileUser): int
    {
        return $this->postsQuery($profileUser)->count();
    }

    /**
     * Videos Vidu recientes visibles en el perfil (vista previa).
     */
    public function recentViduVideos(User $profileUser, int $limit = self::PREVIEW_LIMIT): Collection
    {
        return $this->viduQuery($profileUser)
            ->limit($limit)
            ->get()
            ->map(fn (ViduVideo $video) => $this->formatViduItem($video));
    }

    /**
     * Videos Vidu paginados del perfil para el historial completo.
     */
    public function paginatedViduVideos(User $profileUser, int $page = 1): LengthAwarePaginator
    {
        return $this->viduQuery($profileUser)
            ->paginate(self::PAGE_SIZE, ['*'], 'page', $page)
            ->through(fn (ViduVideo $video) => $this->formatViduItem($video));
    }

    /**
     * Total de videos Vidu públicos del usuario.
     */
    public function viduTotal(User $profileUser): int
    {
        return $this->viduQuery($profileUser)->count();
    }

    /**
     * Resumen mínimo del perfil para cabeceras de páginas de historial.
     */
    public function profileHeader(User $profileUser): array
    {
        return [
            'username' => $profileUser->username,
            'nick' => $profileUser->nick,
            'avatar_url' => $profileUser->avatar_url,
            'tipo_verificacion' => $profileUser->displayVerificationTipo(),
        ];
    }

    /**
     * Query base de posts públicos del perfil (sin borradores ni privados).
     */
    private function postsQuery(User $profileUser)
    {
        return Post::query()
            ->select([
                'id',
                'user_id',
                'category_id',
                'title',
                'slug',
                'points_count',
                'comments_count',
                'views_count',
                'created_at',
            ])
            ->published()
            ->where('user_id', $profileUser->id)
            ->with('category:id,name,slug')
            ->orderByDesc('created_at');
    }

    /**
     * Query base de videos Vidu públicos del perfil.
     */
    private function viduQuery(User $profileUser)
    {
        return ViduVideo::query()
            ->public()
            ->where('user_id', $profileUser->id)
            ->orderByDesc('created_at');
    }

    /**
     * Serializa un post para listados del perfil.
     */
    private function formatPostItem(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'points_count' => $post->points_count,
            'comments_count' => $post->comments_count,
            'views_count' => $post->views_count,
            'created_at' => $post->created_at?->toISOString(),
            'created_at_human' => $post->created_at?->diffForHumans(),
            'category' => $post->category ? [
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
        ];
    }

    /**
     * Serializa un video Vidu para listados del perfil.
     */
    private function formatViduItem(ViduVideo $video): array
    {
        $duration = (int) $video->duration_seconds;
        $minutes = intdiv($duration, 60);
        $seconds = $duration % 60;

        return [
            'id' => $video->id,
            'title' => $video->title ?: 'Video sin título',
            'thumbnail_url' => $video->thumbnail_url,
            'duration_seconds' => $duration,
            'duration_label' => sprintf('%d:%02d', $minutes, $seconds),
            'likes_count' => $video->likes_count,
            'views_count' => $video->views_count,
            'created_at_human' => $video->created_at?->diffForHumans(),
        ];
    }
}
