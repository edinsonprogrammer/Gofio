<?php

/**
 * Migra datos de rangos: añade slug, post_permissions y sincroniza registros existentes.
 */

use App\Models\RolRango;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $rangos = [
            ['nombre' => 'Administrador', 'color' => '#D6030B', 'icon' => 'fa-solid fa-crown', 'puntos_requeridos' => 0, 'poder_voto' => 5, 'limite_voto_diario' => 50, 'is_staff' => true, 'auto_promote' => false],
            ['nombre' => 'Moderador', 'color' => '#FF9900', 'icon' => 'fa-solid fa-shield-halved', 'puntos_requeridos' => 0, 'poder_voto' => 4, 'limite_voto_diario' => 40, 'is_staff' => true, 'auto_promote' => false],
            ['nombre' => 'Newbie', 'color' => '#171717', 'icon' => 'fa-solid fa-seedling', 'puntos_requeridos' => 0, 'poder_voto' => 1, 'limite_voto_diario' => 5, 'is_staff' => false, 'auto_promote' => true],
            ['nombre' => 'Usuario', 'color' => '#0198E7', 'icon' => 'fa-solid fa-star', 'puntos_requeridos' => 50, 'poder_voto' => 2, 'limite_voto_diario' => 10, 'is_staff' => false, 'auto_promote' => true],
            ['nombre' => 'Advanced', 'color' => '#00CCFF', 'icon' => 'fa-solid fa-star-half-stroke', 'puntos_requeridos' => 500, 'poder_voto' => 2, 'limite_voto_diario' => 15, 'is_staff' => false, 'auto_promote' => true],
            ['nombre' => 'Platinum', 'color' => '#01A021', 'icon' => 'fa-solid fa-medal', 'puntos_requeridos' => 2000, 'poder_voto' => 3, 'limite_voto_diario' => 20, 'is_staff' => false, 'auto_promote' => true],
            ['nombre' => 'Diamond', 'color' => '#CC6600', 'icon' => 'fa-solid fa-gem', 'puntos_requeridos' => 10000, 'poder_voto' => 5, 'limite_voto_diario' => 40, 'is_staff' => false, 'auto_promote' => true],
        ];

        foreach ($rangos as $rango) {
            RolRango::updateOrCreate(['nombre' => $rango['nombre']], $rango);
        }

        $adminRank = RolRango::where('nombre', 'Administrador')->first();
        $newbieRank = RolRango::where('nombre', 'Newbie')->first();

        if ($adminRank) {
            User::where('is_admin', true)->update(['rango_id' => $adminRank->id]);
        }

        if ($newbieRank) {
            User::where('is_admin', false)
                ->whereDoesntHave('rango', fn ($q) => $q->where('is_staff', true))
                ->whereNull('rango_id')
                ->update(['rango_id' => $newbieRank->id]);
        }
    }

    public function down(): void
    {
        // Datos de referencia; no revertir.
    }
};
