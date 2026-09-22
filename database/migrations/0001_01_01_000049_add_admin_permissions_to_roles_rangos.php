<?php

/**
 * Añade permisos de pestañas del panel admin a los rangos staff.
 */

use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna JSON y asigna permisos por defecto al rango Moderador.
     */
    public function up(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->json('admin_permissions')->nullable()->after('post_permissions');
        });

        DB::table('roles_rangos')
            ->where('slug', 'moderador')
            ->update([
                'admin_permissions' => json_encode(AdminPermissions::moderatorDefaults()),
            ]);

        DB::table('roles_rangos')
            ->where('slug', 'administrador')
            ->update([
                'admin_permissions' => json_encode(AdminPermissions::allTabKeys()),
            ]);
    }

    /**
     * Elimina la columna de permisos del panel admin.
     */
    public function down(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->dropColumn('admin_permissions');
        });
    }
};
