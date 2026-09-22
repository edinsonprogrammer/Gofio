<?php

namespace App\Services;

/**
 * Registra votos en posts y comentarios aplicando límites diarios y detección de karma farming.
 */

use App\Models\AppNotification;
use App\Models\Comment;
use App\Models\ModerationAlert;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\VoteLogRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoteService
{
    /**
     * Inyecta repositorios de votos, contenido, límites de actividad y efectos asíncronos.
     */
    public function __construct(
        private readonly VoteLogRepository $voteLogRepository,
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly NotificationService $notificationService,
        private readonly UserActivityLimitService $activityLimitService,
        private readonly AsyncSideEffects $asyncSideEffects,
    ) {}

    /**
     * Registra un voto positivo en un post validando autor, duplicados diarios y límites del votante.
     */
    public function votePost(User $voter, Post $post): array
    {
        if ($post->user_id === $voter->id) {
            throw ValidationException::withMessages([
                'vote' => ['No puedes votar tu propio post.'],
            ]);
        }

        return $this->processVote($voter, $post->user, function () use ($voter, $post) {
            if ($this->voteLogRepository->hasVotedPostToday($voter->id, $post->id)) {
                throw ValidationException::withMessages([
                    'vote' => ['Ya votaste este post hoy.'],
                ]);
            }

            return ['post_id' => $post->id, 'comment_id' => null];
        }, function (int $points) use ($post) {
            $this->postRepository->incrementPoints($post, $points);
        }, function (int $points) use ($post) {
            $post->user->increment('karma', $points);
        });
    }

    /**
     * Registra un voto positivo en un comentario con las mismas reglas anti-abuso que en posts.
     */
    public function voteComment(User $voter, Comment $comment): array
    {
        if ($comment->user_id === $voter->id) {
            throw ValidationException::withMessages([
                'vote' => ['No puedes votar tu propio comentario.'],
            ]);
        }

        return $this->processVote($voter, $comment->user, function () use ($voter, $comment) {
            if ($this->voteLogRepository->hasVotedCommentToday($voter->id, $comment->id)) {
                throw ValidationException::withMessages([
                    'vote' => ['Ya votaste este comentario hoy.'],
                ]);
            }

            return ['post_id' => null, 'comment_id' => $comment->id];
        }, function (int $points) use ($comment) {
            $this->commentRepository->incrementPoints($comment, $points);
        }, function (int $points) use ($comment) {
            $comment->user->increment('karma', $points);
        });
    }

    /**
     * Núcleo transaccional del voto: valida cuota diaria, detecta farming y aplica puntos o congelamiento.
     */
    private function processVote(
        User $voter,
        User $author,
        callable $validateTarget,
        callable $applyPointsToTarget,
        callable $applyKarmaToAuthor,
    ): array {
        $voter->loadMissing('rango');

        $dailyLimit = $this->activityLimitService->maxVotesPerDay($voter);
        $votePower = $voter->rango?->poder_voto ?? 1;

        $frozen = false;

        return DB::transaction(function () use (
            $voter,
            $author,
            $validateTarget,
            $applyPointsToTarget,
            $applyKarmaToAuthor,
            $votePower,
            $dailyLimit,
            &$frozen
        ) {
            // Serializa votos del mismo usuario para evitar condiciones de carrera en cuota y duplicados.
            User::query()->whereKey($voter->id)->lockForUpdate()->first();

            if ($this->voteLogRepository->countVotesToday($voter->id) >= $dailyLimit) {
                throw ValidationException::withMessages([
                    'vote' => ["Has alcanzado tu límite diario de {$dailyLimit} votos."],
                ]);
            }

            $target = $validateTarget();

            // Detectar votos cruzados recíprocos y congelar puntos generando alerta de moderación
            if ($this->voteLogRepository->hasMutualVoteFarming($voter->id, $author->id)) {
                $frozen = true;

                ModerationAlert::create([
                    'user_id' => $voter->id,
                    'related_user_id' => $author->id,
                    'type' => 'karma_farming',
                    'status' => 'open',
                    'reason' => 'Votos cruzados mutuos detectados en las últimas 48 horas. Puntos congelados.',
                    'metadata' => $target,
                ]);

                $this->notificationService->notifyStaff(
                    AppNotification::TYPE_MODERATOR_ACTION,
                    'Alerta de karma farming',
                    "Votos cruzados entre @{$voter->username} y @{$author->username}. Puntos congelados.",
                    '/admin/moderacion',
                    [
                        'actor_id' => $voter->id,
                        'related_user_id' => $author->id,
                        'type' => 'karma_farming',
                    ],
                );
            }

            $karmaPoints = $author->isCreatorPlus() ? $votePower * 2 : $votePower;

            $this->voteLogRepository->create([
                'user_id' => $voter->id,
                'post_id' => $target['post_id'],
                'comment_id' => $target['comment_id'],
                'points_given' => $frozen ? 0 : $votePower,
                'created_at' => now(),
            ]);

            if (! $frozen) {
                $applyPointsToTarget($votePower);
                $applyKarmaToAuthor($karmaPoints);
                $this->asyncSideEffects->queueGamificationSync($author->id);
            }

            return [
                'success' => true,
                'points_given' => $frozen ? 0 : $votePower,
                'frozen' => $frozen,
                'message' => $frozen
                    ? 'Voto registrado pero puntos congelados por sospecha de karma farming.'
                    : 'Voto registrado correctamente.',
            ];
        });
    }
}
