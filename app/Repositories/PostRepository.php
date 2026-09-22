<?php

namespace App\Repositories;

/**
 * Acceso a datos de posts: feed, búsqueda por slug, creación y contadores de engagement.
 */

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PostRepository
{
    /** Expresión SQL de la fecha de publicación (sin hora) para agrupar el feed por día. */
    private const FEED_DATE_EXPR = 'DATE(created_at)';

    /** Orden pseudo-aleatorio estable por día e ID; evita duplicados al paginar el feed. */
    private const FEED_DAY_SHUFFLE_EXPR = "CRC32(CONCAT(DATE(created_at), '-', id))";

    /**
     * Pagina el feed publicado: fijados primero, luego por fecha (sin hora) y mezcla estable por día.
     */
    public function getPublishedFeed(int $perPage = 10, int $page = 1, ?int $categoryId = null): LengthAwarePaginator
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
                'tips_total',
                'tips_count',
                'status',
                'tags',
                'block_comments',
                'is_private',
                'is_sticky',
                'is_featured',
                'created_at',
                'updated_at',
            ])
            ->published()
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderByDesc('is_sticky')
            ->orderByRaw(self::FEED_DATE_EXPR.' DESC')
            ->orderByRaw(self::FEED_DAY_SHUFFLE_EXPR.' ASC')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Localiza un post publicado y visible por su slug URL.
     */
    public function findBySlug(string $slug): ?Post
    {
        return Post::query()
            ->published()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Busca un post por slug incluyendo borradores y privados (excluye baneados).
     */
    public function findBySlugIncludingPrivate(string $slug): ?Post
    {
        return Post::query()
            ->where('slug', $slug)
            ->whereIn('status', ['published', 'draft'])
            ->first();
    }

    /**
     * Obtiene un post por identificador numérico primario.
     */
    public function findById(int $id): ?Post
    {
        return Post::query()->find($id);
    }

    /**
     * Persiste un nuevo registro de post con los atributos proporcionados.
     */
    public function create(array $data): Post
    {
        return Post::create($data);
    }

    /**
     * Comprueba si ya existe un post con el slug indicado.
     */
    public function slugExists(string $slug): bool
    {
        return Post::where('slug', $slug)->exists();
    }

    /**
     * Incrementa en uno el contador de visualizaciones del post.
     */
    public function incrementViews(Post $post): void
    {
        $post->increment('views_count');
    }

    /**
     * Suma puntos de voto al contador agregado del post.
     */
    public function incrementPoints(Post $post, int $points): void
    {
        $post->increment('points_count', $points);
    }

    /**
     * Incrementa en uno el número de comentarios asociados al post.
     */
    public function incrementCommentsCount(Post $post): void
    {
        $post->increment('comments_count');
    }

    /**
     * Lista posts publicados de un usuario ordenados por fecha de creación descendente.
     */
    public function getByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
