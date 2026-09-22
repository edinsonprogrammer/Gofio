<?php

/**
 * Migración para añadir layout_variant y logo_url a la tabla de temas.
 * Permite que cada tema controle la estructura de página además de los colores.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el campo de variante de layout y la URL de logo personalizado al esquema de temas.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // Variante estructural: default | sidebar-nav | minimal | compact | panel
            $table->string('layout_variant', 40)->default('default')->after('icon_pack_slug');
            // URL de logo personalizado para el tema (puede ser ruta relativa o URL absoluta)
            $table->string('logo_url', 512)->nullable()->after('layout_variant');
        });
    }

    /**
     * Elimina los campos añadidos.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['layout_variant', 'logo_url']);
        });
    }
};
