<?php

namespace App\Services;

/**
 * Gestiona reacciones a posts, incluyendo toggle, votos asociados y evaluación de popularidad.
 */

use App\Jobs\EvaluatePostPopularityJob;
use App\Models\AppNotification;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use App\Repositories\PostReactionRepository;
use Illuminate\Validation\ValidationException;

class ReactionService
{
    /**
     * Inyecta repositorio de reacciones, votos, efectos asíncronos y reglas de karma.
     */
    public function __construct(
        private readonly PostReactionRepository $reactionRepository,
        private readonly VoteService $voteService,
        private readonly AsyncSideEffects $asyncSideEffects,
        private readonly KarmaRuleService $karmaRuleService,
    ) {}

    /**
     * Registra, actualiza o elimina la reacción del usuario y dispara voto, notificación y job de popularidad.
     */
    public function react(User $user, Post $post, string $reaction): array
    {
        if (! in_array($reaction, PostReaction::TYPES, true)) {
            throw ValidationException::withMessages([
                'reaction' => ['Tipo de reacción no válido.'],
            ]);
        }

        if ($post->user_id === $user->id) {
            throw ValidationException::withMessages([
                'reaction' => ['No puedes reaccionar a tu propio post.'],
            ]);
        }

        $existing = $this->reactionRepository->findForUser($post->id, $user->id);
        $pointsResult = null;
        $removed = false;

        if ($existing?->reaction === $reaction) {
            $this->reactionRepository->remove($post->id, $user->id);
            $removed = true;
        } else {
            $isFirstReaction = $existing === null;

            $this->reactionRepository->set($post->id, $user->id, $reaction);

            // Primera reacción del usuario: intenta otorgar puntos de voto al autor
            if ($isFirstReaction) {
                try {
                    $pointsResult = $this->voteService->votePost($user, $post);
                } catch (ValidationException $e) {
                    $pointsResult = ['points_given' => 0, 'frozen' => false, 'message' => null];
                }

                // Karma por recibir una reacción (regla post_reacted)
                $post->loadMissing('user');
                $this->karmaRuleService->evaluatePostReacted($post->user, $post->id);
            }

            $post->loadMissing('user');
            $this->asyncSideEffects->queueNotification(
                $post->user,
                AppNotification::TYPE_REACTION_RECEIVED,
                'Nueva reacción',
                "@{$user->username} reaccionó con {$this->reactionLabel($reaction)} a tu post «{$post->title}».",
                "/post/{$post->slug}",
                [
                    'actor_id' => $user->id,
                    'post_id' => $post->id,
                    'post_slug' => $post->slug,
                    'reaction' => $reaction,
                ],
            );

            $summary = $this->reactionRepository->summaryForPost($post->id, $user->id);
            EvaluatePostPopularityJob::dispatch($post->id, (int) ($summary['total'] ?? 0));
        }

        $summary = $this->reactionRepository->summaryForPost($post->id, $user->id);

        return [
            'removed' => $removed,
            'reaction' => $removed ? null : $reaction,
            'reactions' => $summary,
            'points_given' => $pointsResult['points_given'] ?? 0,
            'frozen' => $pointsResult['frozen'] ?? false,
            'message' => $removed
                ? 'Reacción quitada.'
                : ($pointsResult['message'] ?? 'Reacción registrada.'),
        ];
    }

    /**
     * Obtiene resúmenes de reacciones para varios posts, incluyendo la del usuario autenticado.
     */
    public function summariesForPosts(array $postIds, ?User $user = null): array
    {
        return $this->reactionRepository->summariesForPosts($postIds, $user?->id);
    }

    /**
     * Traduce el identificador interno de reacción a una etiqueta legible en español.
     */
    private function reactionLabel(string $reaction): string
    {
        return match ($reaction) {
            'like' => 'Me gusta',
            'excelente' => 'Excelente',
            'lindo' => 'Lindo',
            'desacuerdo' => 'Desacuerdo',
            'asombroso' => 'Asombroso',
            default => $reaction,
        };
    }
}
