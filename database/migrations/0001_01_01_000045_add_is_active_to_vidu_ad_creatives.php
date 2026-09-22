<?php

/**
 * Añade el campo is_active a los creativos publicitarios para permitir
 * rotación aleatoria entre varios videos activos en lugar de uno único.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vidu_ad_creatives', function (Blueprint $table) {
            // Cada creativo puede estar activo o inactivo; los activos rotan de forma aleatoria.
            $table->boolean('is_active')->default(false)->after('duration_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('vidu_ad_creatives', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
