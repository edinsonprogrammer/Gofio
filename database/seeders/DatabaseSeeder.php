<?php

/**
 * Seeder raíz que orquesta la siembra inicial de datos de desarrollo.
 */

namespace Database\Seeders;

use App\Models\RolRango;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta seeders de rangos, categorías, medallas, premios, admin, posts, plataforma y temas.
     */
    public function run(): void
    {
        $this->call([
            RolesRangosSeeder::class,
            CategoriesSeeder::class,
            MedalsSeeder::class,
            AwardsSeeder::class,
        ]);

        $adminRank = RolRango::where('nombre', 'Administrador')->first();

        User::updateOrCreate(
            ['email' => 'admin@gofio.test'],
            [
                'username' => 'admin',
                'password' => Hash::make('password'),
                'rango_id' => $adminRank?->id ?? 1,
                'karma' => 0,
                'balance_monedas' => 500,
                'tipo_verificacion' => 'none',
                'is_admin' => true,
            ]
        );

        $this->call(PostsSeeder::class);
        $this->call(PlatformUserSeeder::class);
        $this->call(ThemesSeeder::class);
    }
}
