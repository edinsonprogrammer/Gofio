<?php

/**
 * Seeder que crea categorías y premios de reconocimiento comunitario.
 */

namespace Database\Seeders;

use App\Models\Award;
use App\Models\AwardCategory;
use Illuminate\Database\Seeder;

class AwardsSeeder extends Seeder
{
    /**
     * Crea categorías Comunidad y Contenido con cuatro premios de ejemplo.
     */
    public function run(): void
    {
        $community = AwardCategory::updateOrCreate(
            ['name' => 'Comunidad'],
            ['sort_order' => 1],
        );

        $content = AwardCategory::updateOrCreate(
            ['name' => 'Contenido'],
            ['sort_order' => 2],
        );

        $awards = [
            [
                'award_category_id' => $community->id,
                'name' => 'Miembro destacado',
                'description' => 'Contribución excepcional a la comunidad.',
                'icon' => 'fa-solid fa-award',
                'color' => '#F39C12',
                'sort_order' => 1,
            ],
            [
                'award_category_id' => $community->id,
                'name' => 'Ayudante del mes',
                'description' => 'Siempre dispuesto a ayudar a otros usuarios.',
                'icon' => 'fa-solid fa-handshake',
                'color' => '#27AE60',
                'sort_order' => 2,
            ],
            [
                'award_category_id' => $content->id,
                'name' => 'Post del año',
                'description' => 'El mejor contenido publicado en el año.',
                'icon' => 'fa-solid fa-crown',
                'color' => '#CC6600',
                'sort_order' => 1,
            ],
            [
                'award_category_id' => $content->id,
                'name' => 'Creador legendario',
                'description' => 'Trayectoria sobresaliente como autor.',
                'icon' => 'fa-solid fa-gem',
                'color' => '#8E44AD',
                'sort_order' => 2,
            ],
        ];

        foreach ($awards as $award) {
            Award::updateOrCreate(
                ['name' => $award['name'], 'award_category_id' => $award['award_category_id']],
                $award,
            );
        }
    }
}
