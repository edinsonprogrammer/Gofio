<?php

/**
 * Añade slug a roles_rangos y medals, y rango_locked a users para bloquear ascenso automático.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->string('slug', 60)->nullable()->after('nombre');
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->string('slug', 60)->nullable()->after('title');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('rango_locked')->default(false)->after('rango_id');
        });

        foreach (DB::table('roles_rangos')->select('id', 'nombre')->get() as $rango) {
            DB::table('roles_rangos')->where('id', $rango->id)->update([
                'slug' => $this->uniqueSlug('roles_rangos', $rango->nombre, $rango->id),
            ]);
        }

        foreach (DB::table('medals')->select('id', 'title')->get() as $medal) {
            DB::table('medals')->where('id', $medal->id)->update([
                'slug' => $this->uniqueSlug('medals', $medal->title, $medal->id),
            ]);
        }

        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rango_locked');
        });
    }

    private function uniqueSlug(string $table, string $name, int $id): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (DB::table($table)->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
};
