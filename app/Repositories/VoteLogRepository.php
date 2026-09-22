<?php

namespace App\Repositories;

/**
 * Registro y consultas de votos diarios, incluyendo detección de patrones de karma farming.
 */

use App\Models\VoteLog;
use Illuminate\Support\Carbon;

class VoteLogRepository
{
    /**
     * Comprueba si el usuario ya votó un post específico durante el día calendario actual.
     */
    public function hasVotedPostToday(int $userId, int $postId): bool
    {
        return VoteLog::query()
            ->where('user_id', $userId)
            ->where('post_id', $postId)
            ->where('created_at', '>=', Carbon::today())
            ->exists();
    }

    /**
     * Comprueba si el usuario ya votó un comentario específico durante el día calendario actual.
     */
    public function hasVotedCommentToday(int $userId, int $commentId): bool
    {
        return VoteLog::query()
            ->where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->where('created_at', '>=', Carbon::today())
            ->exists();
    }

    /**
     * Cuenta cuántos votos ha emitido el usuario desde el inicio del día actual.
     */
    public function countVotesToday(int $userId): int
    {
        return VoteLog::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', Carbon::today())
            ->count();
    }

    /**
     * Persiste un nuevo registro de voto con post o comentario objetivo y puntos otorgados.
     */
    public function create(array $data): VoteLog
    {
        return VoteLog::create($data);
    }

    /**
     * Detecta votos cruzados recíprocos entre dos usuarios en las últimas 48 horas.
     */
    public function hasMutualVoteFarming(int $voterId, int $authorId): bool
    {
        $since = Carbon::now()->subHours(48);

        $voterToAuthor = VoteLog::query()
            ->where('user_id', $voterId)
            ->where('created_at', '>=', $since)
            ->where(function ($query) use ($authorId) {
                $query->whereHas('post', fn ($q) => $q->where('user_id', $authorId))
                    ->orWhereHas('comment', fn ($q) => $q->where('user_id', $authorId));
            })
            ->exists();

        if (! $voterToAuthor) {
            return false;
        }

        return VoteLog::query()
            ->where('user_id', $authorId)
            ->where('created_at', '>=', $since)
            ->where(function ($query) use ($voterId) {
                $query->whereHas('post', fn ($q) => $q->where('user_id', $voterId))
                    ->orWhereHas('comment', fn ($q) => $q->where('user_id', $voterId));
            })
            ->exists();
    }
}
