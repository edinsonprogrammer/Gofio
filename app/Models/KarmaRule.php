<?php

namespace App\Models;

/**
 * Modelo Eloquent para las reglas de karma configurables desde el panel de administración.
 * Cada regla define cuántos puntos otorgar, cuándo (trigger_type) y cómo deduplicar.
 */

use Illuminate\Database\Eloquent\Model;

class KarmaRule extends Model
{
    /** @var list<string> Campos asignables en masa. */
    protected $fillable = [
        'key',
        'trigger_type',
        'label',
        'description',
        'karma_points',
        'enabled',
        'dedup_mode',
        'conditions',
        'is_system',
        'sort_order',
    ];

    /** @var array<string, string> Conversiones automáticas de tipos. */
    protected $casts = [
        'karma_points' => 'integer',
        'enabled'      => 'boolean',
        'conditions'   => 'array',
        'is_system'    => 'boolean',
        'sort_order'   => 'integer',
    ];

    /**
     * Tipos de trigger disponibles con su etiqueta descriptiva para el admin.
     * Cada clave debe tener un método evaluate* correspondiente en KarmaRuleService.
     */
    public static function triggerTypes(): array
    {
        return [
            // ─── Posts ─────────────────────────────────────────────────────
            'post_created'       => 'Publicar un post',
            'comment_created'    => 'Comentar en un post',
            'profile_completed'  => 'Completar el perfil',
            'video_in_post'      => 'Incluir video en un post',
            'popular_post'       => 'Post popular (reacciones)',
            'post_reacted'       => 'Tu post recibe una reacción',
            // ─── Seguidores y propinas ─────────────────────────────────────
            'followers_milestone'=> 'Hito de seguidores',
            'tip_sent'           => 'Enviar propina',
            'tip_received'       => 'Recibir propina',
            // ─── Rangos ────────────────────────────────────────────────────
            'rank_promoted'      => 'Subir de rango',
            // ─── Vidu Reels ────────────────────────────────────────────────
            'vidu_uploaded'      => 'Subir un Vidu Reel',
            'vidu_liked'         => 'Tu Vidu Reel recibe un like',
            'vidu_popular'       => 'Vidu Reel popular (likes)',
        ];
    }

    /**
     * Modos de deduplicación disponibles con descripción legible.
     */
    public static function dedupModes(): array
    {
        return [
            'once'          => 'Una vez por usuario (total)',
            'per_reference' => 'Una vez por objeto (post, comentario…)',
            'unlimited'     => 'Sin límite',
        ];
    }

    /**
     * Devuelve la condición min_reactions si la regla es de tipo popular_post.
     */
    public function getMinReactions(): int
    {
        return (int) ($this->conditions['min_reactions'] ?? 10);
    }

    /**
     * Devuelve la condición every si la regla es de tipo followers_milestone.
     */
    public function getEvery(): int
    {
        return max(1, (int) ($this->conditions['every'] ?? 10));
    }

    /**
     * Devuelve el mínimo de likes requeridos para la regla vidu_popular.
     */
    public function getMinLikes(): int
    {
        return max(1, (int) ($this->conditions['min_likes'] ?? 10));
    }

    /**
     * Scope para obtener solo las reglas activas de un trigger específico.
     */
    public function scopeForTrigger($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType)->where('enabled', true);
    }
}
