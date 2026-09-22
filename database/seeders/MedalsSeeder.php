<?php

/**
 * Seeder que crea medallas de gamificación con sus condiciones de obtención.
 */

namespace Database\Seeders;

use App\Models\Medal;
use Illuminate\Database\Seeder;

class MedalsSeeder extends Seeder
{
    /**
     * Crea siete medallas de gamificación con condiciones automáticas.
     */
    public function run(): void
    {
        $medals = [
            [
                'title' => 'Primer paso',
                'description' => 'Publicaste tu primer post en Gofio.',
                'icon' => 'fa-solid fa-pen-nib',
                'color' => '#0198E7',
                'condition_type' => 'posts',
                'condition_value' => 1,
                'sort_order' => 1,
            ],
            [
                'title' => 'Conversador',
                'description' => 'Dejaste al menos 10 comentarios.',
                'icon' => 'fa-solid fa-comments',
                'color' => '#65676B',
                'condition_type' => 'comments',
                'condition_value' => 10,
                'sort_order' => 2,
            ],
            [
                'title' => 'Creador activo',
                'description' => 'Publicaste 10 posts en la comunidad.',
                'icon' => 'fa-solid fa-fire',
                'color' => '#E74C3C',
                'condition_type' => 'posts',
                'condition_value' => 10,
                'sort_order' => 3,
            ],
            [
                'title' => 'Karma 100',
                'description' => 'Alcanzaste 100 puntos de karma.',
                'icon' => 'fa-solid fa-bolt',
                'color' => '#F39C12',
                'condition_type' => 'karma',
                'condition_value' => 100,
                'sort_order' => 4,
            ],
            [
                'title' => 'Karma 1000',
                'description' => 'Alcanzaste 1000 puntos de karma.',
                'icon' => 'fa-solid fa-trophy',
                'color' => '#CC6600',
                'condition_type' => 'karma',
                'condition_value' => 1000,
                'sort_order' => 5,
            ],
            [
                'title' => 'Verificado',
                'description' => 'Cuenta verificada en la comunidad.',
                'icon' => 'fa-solid fa-circle-check',
                'color' => '#1877F2',
                'condition_type' => 'verified',
                'condition_value' => 1,
                'sort_order' => 6,
            ],
            [
                'title' => 'Staff pick',
                'description' => 'Reconocimiento especial del equipo.',
                'icon' => 'fa-solid fa-hand-sparkles',
                'color' => '#9B59B6',
                'condition_type' => 'manual',
                'condition_value' => 0,
                'sort_order' => 7,
            ],
        ];

        foreach ($medals as $medal) {
            Medal::updateOrCreate(['title' => $medal['title']], $medal);
        }
    }
}
