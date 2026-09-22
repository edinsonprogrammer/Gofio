<?php

/**
 * Establece el tema Menta Tecnológico como tema por defecto de la plataforma.
 */

use App\Models\Theme;
use App\Support\ThemeVariables;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $mint = ThemeVariables::defaults();

        Theme::query()->update(['is_default' => false]);

        Theme::updateOrCreate(
            ['slug' => 'mint'],
            [
                'name' => 'Menta Tecnológico',
                'description' => 'Verde menta profesional con neutros slate — identidad principal de Gofio.',
                'variables' => $mint,
                'is_default' => true,
                'requires_creator_plus' => false,
                'is_active' => true,
            ]
        );

        Theme::where('slug', 'classic')->update(['is_default' => false]);
    }

    public function down(): void
    {
        // Sin revertir colores de marca.
    }
};
