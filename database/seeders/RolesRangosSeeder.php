<?php

/**
 * Seeder que crea los rangos de usuario (Administrador, Moderador, Newbie, etc.).
 */

namespace Database\Seeders;

use App\Models\RolRango;
use Illuminate\Database\Seeder;

class RolesRangosSeeder extends Seeder
{
    /**
     * Crea o actualiza los siete rangos base de la plataforma.
     */
    public function run(): void
    {
        $rangos = [
            [
                'nombre' => 'Administrador',
                'color' => '#D6030B',
                'icon' => 'fa-solid fa-crown',
                'puntos_requeridos' => 0,
                'poder_voto' => 5,
                'limite_voto_diario' => 50,
                'is_staff' => true,
                'auto_promote' => false,
            ],
            [
                'nombre' => 'Moderador',
                'color' => '#FF9900',
                'icon' => 'fa-solid fa-shield-halved',
                'puntos_requeridos' => 0,
                'poder_voto' => 4,
                'limite_voto_diario' => 40,
                'is_staff' => true,
                'auto_promote' => false,
            ],
            [
                'nombre' => 'Newbie',
                'color' => '#171717',
                'icon' => 'fa-solid fa-seedling',
                'puntos_requeridos' => 0,
                'poder_voto' => 1,
                'limite_voto_diario' => 5,
                'is_staff' => false,
                'auto_promote' => true,
            ],
            [
                'nombre' => 'Usuario',
                'color' => '#0198E7',
                'icon' => 'fa-solid fa-star',
                'puntos_requeridos' => 50,
                'poder_voto' => 2,
                'limite_voto_diario' => 10,
                'is_staff' => false,
                'auto_promote' => true,
            ],
            [
                'nombre' => 'Advanced',
                'color' => '#00CCFF',
                'icon' => 'fa-solid fa-star-half-stroke',
                'puntos_requeridos' => 500,
                'poder_voto' => 2,
                'limite_voto_diario' => 15,
                'is_staff' => false,
                'auto_promote' => true,
            ],
            [
                'nombre' => 'Platinum',
                'color' => '#01A021',
                'icon' => 'fa-solid fa-medal',
                'puntos_requeridos' => 2000,
                'poder_voto' => 3,
                'limite_voto_diario' => 20,
                'is_staff' => false,
                'auto_promote' => true,
            ],
            [
                'nombre' => 'Diamond',
                'color' => '#CC6600',
                'icon' => 'fa-solid fa-gem',
                'puntos_requeridos' => 10000,
                'poder_voto' => 5,
                'limite_voto_diario' => 40,
                'is_staff' => false,
                'auto_promote' => true,
            ],
        ];

        foreach ($rangos as $rango) {
            RolRango::updateOrCreate(['nombre' => $rango['nombre']], $rango);
        }
    }
}
