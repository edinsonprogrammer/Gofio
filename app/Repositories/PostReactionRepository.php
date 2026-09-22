<?php

namespace App\Repositories;

/**
 * Persistencia y consultas agregadas de reacciones emoji en posts.
 */

use App\Models\PostReaction;
use Illuminate\Support\Collection;

class PostReactionRepository
{
    /**
     * Obtiene conteo por tipo, total y reacción del usuario para un post individual.
     */
    public function summaryForPost(int $postId, ?int $userId = null): array
    {
        $rows = PostReaction::query()
            ->where('post_id', $postId)
            ->selectRaw('reaction, COUNT(*) as total')
            ->groupBy('reaction')
            ->pluck('total', 'reaction');

        $summary = [];
        foreach (PostReaction::TYPES as $type) {
            $summary[$type] = (int) ($rows[$type] ?? 0);
        }

        $userReaction = null;
        if ($userId) {
            $userReaction = PostReaction::query()
                ->where('post_id', $postId)
                ->where('user_id', $userId)
                ->value('reaction');
        }

        return [
            'summary' => $summary,
            'total' => array_sum($summary),
            'user_reaction' => $userReaction,
        ];
    }

    /**
     * Calcula resúmenes de reacciones para múltiples posts en una sola consulta agrupada.
     */
    public function summariesForPosts(array $postIds, ?int $userId = null): array
    {
        if ($postIds === []) {
            return [];
        }

        $counts = PostReaction::query()
            ->whereIn('post_id', $postIds)
            ->selectRaw('post_id, reaction, COUNT(*) as total')
            ->groupBy('post_id', 'reaction')
            ->get();

        $userReactions = collect();
        if ($userId) {
            $userReactions = PostReaction::query()
                ->whereIn('post_id', $postIds)
                ->where('user_id', $userId)
                ->pluck('reaction', 'post_id');
        }

        $result = [];
        foreach ($postIds as $postId) {
            $summary = array_fill_keys(PostReaction::TYPES, 0);
            foreach ($counts->where('post_id', $postId) as $row) {
                $summary[$row->reaction] = (int) $row->total;
            }

            $result[$postId] = [
                'summary' => $summary,
                'total' => array_sum($summary),
                'user_reaction' => $userReactions[$postId] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Busca la reacción registrada de un usuario en un post concreto.
     */
    public function findForUser(int $postId, int $userId): ?PostReaction
    {
        return PostReaction::query()
            ->where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Crea o actualiza la reacción de un usuario sobre un post mediante upsert por par post/usuario.
     */
    public function set(int $postId, int $userId, string $reaction): PostReaction
    {
        return PostReaction::query()->updateOrCreate(
            ['post_id' => $postId, 'user_id' => $userId],
            ['reaction' => $reaction],
        );
    }

    /**
     * Elimina la reacción de un usuario en un post y devuelve si se borró algún registro.
     */
    public function remove(int $postId, int $userId): bool
    {
        return (bool) PostReaction::query()
            ->where('post_id', $postId)
            ->where('user_id', $userId)
            ->delete();
    }
}
