<?php

/**
 * Seeder que crea la cuenta interna de la plataforma y ajusta el saldo del admin.
 */

namespace Database\Seeders;

use App\Models\RolRango;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformUserSeeder extends Seeder
{
    /**
     * Crea la cuenta interna _gofio_platform y ajusta el saldo del admin a 500 monedas.
     */
    public function run(): void
    {
        $newbieRank = RolRango::where('nombre', 'Newbie')->first();

        User::updateOrCreate(
            ['username' => config('gofio.platform_username')],
            [
                'email' => 'platform@gofio.test',
                'password' => Hash::make(str()->random(32)),
                'rango_id' => $newbieRank?->id ?? 3,
                'karma' => 0,
                'balance_monedas' => 0,
                'tipo_verificacion' => 'none',
            ]
        );

        $admin = User::where('username', 'admin')->first();
        if ($admin) {
            $admin->update(['balance_monedas' => 500.00]);
        }
    }
}
