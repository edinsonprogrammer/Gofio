<?php

namespace App\Repositories;

/**
 * Consultas y operaciones de persistencia sobre comentarios de posts.
 */

use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;

class CommentRepository
{
    /**
     * Obtiene comentarios raíz de un post con respuestas y usuarios, ordenados por puntos.
     */
    public function getTopLevelForPost(int $postId, int $limit = 50): Collection
    {
        return Comment::query()
            ->with([
                'user.rango',
                'replies' => fn ($query) => $query
                    ->with('user.rango')
                    ->orderBy('created_at')
                    ->limit(30),
            ])
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->orderByDesc('points_count')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Crea y persiste un nuevo comentario con los datos proporcionados.
     */
    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    /**
     * Busca un comentario por ID incluyendo su autor.
     */
    public function findById(int $id): ?Comment
    {
        return Comment::with('user')->find($id);
    }

    /**
     * Incrementa el contador de puntos del comentario tras un voto.
     */
    public function incrementPoints(Comment $comment, int $points): void
    {
        $comment->increment('points_count', $points);
    }
}
