<?php

/**
 * Crea la tabla roles_rangos con niveles de usuario, poder de voto y permisos de staff.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles_rangos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('puntos_requeridos')->default(0);
            $table->integer('poder_voto')->default(1);
            $table->integer('limite_voto_diario')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_rangos');
    }
};
