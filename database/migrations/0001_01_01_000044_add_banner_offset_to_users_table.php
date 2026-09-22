<?php

/**
 * Añade columnas de posición del banner de perfil para permitir que el usuario
 * reposicione la imagen de portada al gusto (offset horizontal y vertical en %).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Posición horizontal del banner (0–100 %), centrado por defecto.
            $table->unsignedTinyInteger('banner_offset_x')->default(50)->after('banner_url');
            // Posición vertical del banner (0–100 %), centrado por defecto.
            $table->unsignedTinyInteger('banner_offset_y')->default(50)->after('banner_offset_x');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banner_offset_x', 'banner_offset_y']);
        });
    }
};
