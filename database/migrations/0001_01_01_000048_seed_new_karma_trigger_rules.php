<?php

/**
 * Inserta las 5 nuevas reglas del sistema de karma para los triggers:
 * post_reacted, rank_promoted, vidu_uploaded, vidu_liked y vidu_popular.
 */

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Inserta las reglas si aún no existen (idempotente mediante upsert por key).
     */
    public function up(): void
    {
        $now = now();

        $rules = [
            [
                'key'          => 'post_reacted',
                'trigger_type' => 'post_reacted',
                'label'        => 'Tu post recibe una reacción',
                'description'  => 'Se otorga una vez por post cuando otro usuario reacciona por primera vez a él.',
                'karma_points' => 1,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 55,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'key'          => 'rank_promoted',
                'trigger_type' => 'rank_promoted',
                'label'        => 'Subir de rango',
                'description'  => 'Se otorga una vez por rango alcanzado. Si el usuario regresa al mismo rango no vuelve a otorgarse.',
                'karma_points' => 10,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 65,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'key'          => 'vidu_uploaded',
                'trigger_type' => 'vidu_uploaded',
                'label'        => 'Subir un Vidu Reel',
                'description'  => 'Se otorga una vez por video subido exitosamente a la plataforma.',
                'karma_points' => 3,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 90,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'key'          => 'vidu_liked',
                'trigger_type' => 'vidu_liked',
                'label'        => 'Tu Vidu Reel recibe un like',
                'description'  => 'Se otorga una vez por video cuando recibe su primer like de otro usuario.',
                'karma_points' => 2,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 100,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'key'          => 'vidu_popular',
                'trigger_type' => 'vidu_popular',
                'label'        => 'Vidu Reel popular (likes)',
                'description'  => 'Se otorga una vez por video cuando acumula el mínimo de likes configurado.',
                'karma_points' => 20,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => json_encode(['min_likes' => 10]),
                'is_system'    => true,
                'sort_order'   => 110,
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ];

        foreach ($rules as $rule) {
            // Solo inserta si la clave no existe para no sobrescribir configuraciones previas
            \DB::table('karma_rules')->insertOrIgnore($rule);
        }
    }

    /**
     * Elimina las reglas insertadas por esta migración (solo las personalizadas, no las editadas).
     */
    public function down(): void
    {
        \DB::table('karma_rules')->whereIn('key', [
            'post_reacted',
            'rank_promoted',
            'vidu_uploaded',
            'vidu_liked',
            'vidu_popular',
        ])->delete();
    }
};
