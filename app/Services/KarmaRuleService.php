<?php

namespace App\Services;

/**
 * Evalúa y otorga karma según las reglas configuradas en la tabla karma_rules,
 * con control de duplicados por modo de deduplicación (once, per_reference, unlimited).
 */

use App\Models\KarmaRule;
use App\Models\Post;
use App\Models\User;
use App\Models\UserKarmaEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class KarmaRuleService
{
    /**
     * Inyecta el servicio de gamificación que se dispara tras otorgar karma.
     */
    public function __construct(
        private readonly GamificationService $gamificationService,
    ) {}

    // ─── API pública de evaluación ──────────────────────────────────────────

    /**
     * Aplica todas las reglas activas de tipo post_created al publicar un post,
     * incluyendo las reglas video_in_post si el post embebe un video.
     */
    public function evaluatePostCreated(User $user, Post $post): void
    {
        foreach ($this->rulesForTrigger('post_created') as $rule) {
            $this->award($user, $rule, 'post', $post->id);
        }

        if ($this->postHasVideoEmbed($post)) {
            foreach ($this->rulesForTrigger('video_in_post') as $rule) {
                $this->award($user, $rule, 'post', $post->id);
            }
        }
    }

    /**
     * Aplica todas las reglas activas de tipo comment_created al comentar.
     */
    public function evaluateCommentCreated(User $user, int $commentId): void
    {
        foreach ($this->rulesForTrigger('comment_created') as $rule) {
            $this->award($user, $rule, 'comment', $commentId);
        }
    }

    /**
     * Aplica las reglas de profile_completed si el perfil está completo.
     */
    public function evaluateProfileCompleted(User $user): void
    {
        if (! $this->isProfileComplete($user)) {
            return;
        }

        foreach ($this->rulesForTrigger('profile_completed') as $rule) {
            $this->award($user, $rule);
        }
    }

    /**
     * Aplica las reglas de post popular cuando el post supera el mínimo de reacciones
     * definido en cada regla individual (campo conditions.min_reactions).
     */
    public function evaluatePopularPost(User $author, Post $post, int $reactionCount): void
    {
        foreach ($this->rulesForTrigger('popular_post') as $rule) {
            $minReactions = $rule->getMinReactions();

            if ($reactionCount < $minReactions) {
                continue;
            }

            $this->award($author, $rule, 'post', $post->id, [
                'reaction_count' => $reactionCount,
                'min_reactions'  => $minReactions,
            ]);
        }
    }

    /**
     * Aplica las reglas de hitos de seguidores según el valor "every" de cada regla.
     */
    public function evaluateFollowersMilestone(User $target, int $followersCount): void
    {
        foreach ($this->rulesForTrigger('followers_milestone') as $rule) {
            $every     = $rule->getEvery();
            $milestone = intdiv($followersCount, $every) * $every;

            if ($milestone < $every) {
                continue;
            }

            $this->award($target, $rule, 'milestone', $milestone, [
                'followers_count' => $followersCount,
                'every'           => $every,
            ]);
        }
    }

    /**
     * Registra karma al enviar una propina.
     */
    public function evaluateTipSent(User $sender, int $transactionId): void
    {
        foreach ($this->rulesForTrigger('tip_sent') as $rule) {
            $this->award($sender, $rule, 'transaction', $transactionId);
        }
    }

    /**
     * Registra karma al recibir una propina.
     */
    public function evaluateTipReceived(User $receiver, int $transactionId): void
    {
        foreach ($this->rulesForTrigger('tip_received') as $rule) {
            $this->award($receiver, $rule, 'transaction', $transactionId);
        }
    }

    /**
     * Otorga karma al autor del post cuando recibe su primera reacción.
     * El reactor siempre es diferente al autor (validado en ReactionService).
     */
    public function evaluatePostReacted(User $postAuthor, int $postId): void
    {
        foreach ($this->rulesForTrigger('post_reacted') as $rule) {
            $this->award($postAuthor, $rule, 'post', $postId);
        }
    }

    /**
     * Otorga karma al usuario al ascender de rango.
     * Usa awardWithoutSync para evitar recursión con GamificationService.
     */
    public function evaluateRankPromoted(User $user, int $newRangoId): void
    {
        foreach ($this->rulesForTrigger('rank_promoted') as $rule) {
            $this->awardWithoutSync($user, $rule, 'rango', $newRangoId);
        }
    }

    /**
     * Otorga karma al usuario por subir un Vidu Reel.
     */
    public function evaluateViduUploaded(User $user, int $videoId): void
    {
        foreach ($this->rulesForTrigger('vidu_uploaded') as $rule) {
            $this->award($user, $rule, 'vidu', $videoId);
        }
    }

    /**
     * Otorga karma al autor del video cuando alguien le da like por primera vez.
     * No se otorga si el usuario se da like a sí mismo (ViduService ya lo filtra).
     */
    public function evaluateViduLiked(User $videoAuthor, int $videoId): void
    {
        foreach ($this->rulesForTrigger('vidu_liked') as $rule) {
            $this->award($videoAuthor, $rule, 'vidu', $videoId);
        }
    }

    /**
     * Otorga karma al autor cuando su Vidu Reel supera el umbral de likes
     * configurado en conditions.min_likes de cada regla.
     */
    public function evaluateViduPopular(User $videoAuthor, int $videoId, int $likesCount): void
    {
        foreach ($this->rulesForTrigger('vidu_popular') as $rule) {
            $minLikes = $rule->getMinLikes();

            if ($likesCount < $minLikes) {
                continue;
            }

            $this->award($videoAuthor, $rule, 'vidu', $videoId, [
                'likes_count' => $likesCount,
                'min_likes'   => $minLikes,
            ]);
        }
    }

    // ─── Compatibilidad con código heredado ──────────────────────────────────

    /**
     * Devuelve todas las reglas indexadas por key para el panel de configuración.
     * Mantiene compatibilidad con código que llame a rulesForAdmin().
     *
     * @deprecated Usar el CRUD de /admin/karma. Conservado por compatibilidad.
     */
    public function rulesForAdmin(): array
    {
        return KarmaRule::orderBy('sort_order')
            ->get()
            ->keyBy('key')
            ->map(fn (KarmaRule $r) => [
                'label'         => $r->label,
                'enabled'       => $r->enabled,
                'karma'         => $r->karma_points,
                'is_system'     => $r->is_system,
                'trigger_type'  => $r->trigger_type,
                'dedup_mode'    => $r->dedup_mode,
                'conditions'    => $r->conditions,
            ])
            ->toArray();
    }

    // ─── Lógica interna ──────────────────────────────────────────────────────

    /**
     * Otorga karma al usuario según la regla si no fue ya aplicada.
     * Devuelve los puntos otorgados, o 0 si no se aplica.
     */
    public function award(
        User $user,
        KarmaRule $rule,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null,
    ): int {
        if (! $rule->enabled || $rule->karma_points <= 0) {
            return 0;
        }

        // En modo once fijamos referencia al usuario para que el índice único evite duplicados (NULL no cuenta en MySQL).
        [$referenceType, $referenceId] = $this->resolveReferences($rule, $user, $referenceType, $referenceId);

        if ($this->alreadyAwarded($user, $rule, $referenceType, $referenceId)) {
            return 0;
        }

        // Registra el evento en el historial de karma del usuario
        UserKarmaEvent::query()->create([
            'user_id'        => $user->id,
            'rule_key'       => $rule->key,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'karma_awarded'  => $rule->karma_points,
            'metadata'       => $metadata,
        ]);

        $user->increment('karma', $rule->karma_points);
        $this->gamificationService->syncAfterActivity($user->fresh());

        return $rule->karma_points;
    }

    /**
     * Invalida la caché de reglas para que los cambios en el admin surtan efecto inmediatamente.
     */
    public function flushCache(): void
    {
        Cache::forget('karma_rules_by_trigger');
    }

    /**
     * Devuelve las reglas activas de un trigger agrupadas en caché para reducir consultas DB.
     */
    private function rulesForTrigger(string $triggerType): Collection
    {
        // Cache de 10 minutos; se invalida al guardar desde el admin
        $all = Cache::remember('karma_rules_by_trigger', 600, fn () => KarmaRule::where('enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('trigger_type')
        );

        return $all[$triggerType] ?? collect();
    }

    /**
     * Versión de award() sin llamada a syncAfterActivity.
     * Se usa en evaluateRankPromoted para evitar recursión con GamificationService.
     */
    private function awardWithoutSync(
        User $user,
        KarmaRule $rule,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null,
    ): int {
        if (! $rule->enabled || $rule->karma_points <= 0) {
            return 0;
        }

        [$referenceType, $referenceId] = $this->resolveReferences($rule, $user, $referenceType, $referenceId);

        if ($this->alreadyAwarded($user, $rule, $referenceType, $referenceId)) {
            return 0;
        }

        UserKarmaEvent::query()->create([
            'user_id'        => $user->id,
            'rule_key'       => $rule->key,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'karma_awarded'  => $rule->karma_points,
            'metadata'       => $metadata,
        ]);

        $user->increment('karma', $rule->karma_points);

        return $rule->karma_points;
    }

    /**
     * Normaliza reference_type e reference_id según dedup_mode.
     * En modo once, MySQL trataría NULL como distinto y el índice único no bloquearía duplicados.
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function resolveReferences(
        KarmaRule $rule,
        User $user,
        ?string $referenceType,
        ?int $referenceId,
    ): array {
        if ($rule->dedup_mode === 'once') {
            return ['user', $user->id];
        }

        return [$referenceType, $referenceId];
    }

    /**
     * Comprueba si ya se otorgó karma para esta regla según su modo de deduplicación.
     */
    private function alreadyAwarded(
        User $user,
        KarmaRule $rule,
        ?string $referenceType,
        ?int $referenceId,
    ): bool {
        return match ($rule->dedup_mode) {
            // Una vez por usuario en toda la plataforma
            'once' => UserKarmaEvent::query()
                ->where('user_id', $user->id)
                ->where('rule_key', $rule->key)
                ->exists(),

            // Una vez por referencia concreta (post, comentario…)
            'per_reference' => $referenceType !== null && $referenceId !== null
                ? UserKarmaEvent::query()
                    ->where('user_id', $user->id)
                    ->where('rule_key', $rule->key)
                    ->where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId)
                    ->exists()
                : false,

            // Sin límite: nunca bloquea
            'unlimited' => false,

            default => false,
        };
    }

    /**
     * Verifica que avatar, bio, país y edad estén completos para la regla profile_completed.
     */
    private function isProfileComplete(User $user): bool
    {
        return filled($user->avatar_url)
            && filled(trim((string) $user->bio))
            && filled($user->country)
            && $user->age !== null
            && (int) $user->age > 0;
    }

    /**
     * Detecta si el contenido del post incluye un embed de YouTube, Vimeo o TikTok.
     */
    private function postHasVideoEmbed(Post $post): bool
    {
        $blocks = $post->content['blocks'] ?? $post->content ?? [];

        if (! is_array($blocks)) {
            return false;
        }

        $videoServices = ['youtube', 'vimeo', 'tiktok'];

        foreach ($blocks as $block) {
            if (! is_array($block) || ($block['type'] ?? null) !== 'embed') {
                continue;
            }

            $service = strtolower((string) ($block['data']['service'] ?? ''));

            if (in_array($service, $videoServices, true)) {
                return true;
            }
        }

        return false;
    }
}
