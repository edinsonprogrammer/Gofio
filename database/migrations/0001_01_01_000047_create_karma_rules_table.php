<?php

/**
 * Crea la tabla karma_rules para gestionar reglas de karma con CRUD desde el admin.
 * Sustituye el sistema anterior basado en JSON en site_settings.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla e inserta las 8 reglas del sistema que antes vivían como JSON en site_settings.
     */
    public function up(): void
    {
        Schema::create('karma_rules', function (Blueprint $table) {
            $table->id();

            // Identificador único legible por máquina, inmutable una vez creado
            $table->string('key', 80)->unique();

            // Tipo de evento que dispara la regla; determina cuándo se evalúa
            $table->string('trigger_type', 60);

            // Nombre visible en el panel de administración
            $table->string('label', 120);

            // Explicación opcional para el admin sobre cuándo aplica
            $table->string('description', 255)->nullable();

            // Puntos de karma que se otorgan al usuario cuando la regla se cumple
            $table->unsignedSmallInteger('karma_points')->default(1);

            // Si la regla está activa o ignorada durante la evaluación
            $table->boolean('enabled')->default(true);

            // Modo de deduplicación: once (una vez por usuario), per_reference (una vez por objeto) o unlimited
            $table->enum('dedup_mode', ['once', 'per_reference', 'unlimited'])->default('per_reference');

            // Parámetros adicionales específicos por trigger (min_reactions, every, max_per_day, etc.)
            $table->json('conditions')->nullable();

            // Las reglas del sistema no se pueden eliminar desde el admin
            $table->boolean('is_system')->default(false);

            // Orden de aparición en el listado
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('trigger_type');
            $table->index(['enabled', 'trigger_type']);
        });

        // Inserta las 8 reglas del sistema que antes vivían en KarmaRulesDefaults
        \DB::table('karma_rules')->insert([
            [
                'key'          => 'post_created',
                'trigger_type' => 'post_created',
                'label'        => 'Publicar un post',
                'description'  => 'Se otorga cada vez que el usuario publica un nuevo post.',
                'karma_points' => 2,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 10,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'comment_created',
                'trigger_type' => 'comment_created',
                'label'        => 'Comentar en un post',
                'description'  => 'Se otorga cada vez que el usuario deja un comentario.',
                'karma_points' => 1,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 20,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'profile_completed',
                'trigger_type' => 'profile_completed',
                'label'        => 'Completar el perfil',
                'description'  => 'Se otorga una única vez cuando el usuario completa avatar, bio, país y edad.',
                'karma_points' => 10,
                'enabled'      => true,
                'dedup_mode'   => 'once',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 30,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'video_in_post',
                'trigger_type' => 'video_in_post',
                'label'        => 'Incluir video en un post',
                'description'  => 'Se otorga una vez por post cuando se embebe un video de YouTube, Vimeo o TikTok.',
                'karma_points' => 5,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 40,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'popular_post',
                'trigger_type' => 'popular_post',
                'label'        => 'Post popular (reacciones)',
                'description'  => 'Se otorga una vez por post cuando acumula el mínimo de reacciones configurado.',
                'karma_points' => 15,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => json_encode(['min_reactions' => 10]),
                'is_system'    => true,
                'sort_order'   => 50,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'followers_milestone',
                'trigger_type' => 'followers_milestone',
                'label'        => 'Hito de seguidores',
                'description'  => 'Se otorga cada vez que el usuario alcanza un múltiplo del valor "every" en seguidores.',
                'karma_points' => 5,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => json_encode(['every' => 10]),
                'is_system'    => true,
                'sort_order'   => 60,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'tip_sent',
                'trigger_type' => 'tip_sent',
                'label'        => 'Enviar propina',
                'description'  => 'Se otorga una vez por transacción cuando el usuario envía una propina.',
                'karma_points' => 1,
                'enabled'      => true,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 70,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'key'          => 'tip_received',
                'trigger_type' => 'tip_received',
                'label'        => 'Recibir propina',
                'description'  => 'Se otorga una vez por transacción cuando el usuario recibe una propina.',
                'karma_points' => 0,
                'enabled'      => false,
                'dedup_mode'   => 'per_reference',
                'conditions'   => null,
                'is_system'    => true,
                'sort_order'   => 80,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }

    /**
     * Elimina la tabla karma_rules.
     */
    public function down(): void
    {
        Schema::dropIfExists('karma_rules');
    }
};
