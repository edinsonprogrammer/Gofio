<?php

/**
 * Amplía la longitud del campo icon en roles_rangos, medals, awards y categories.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->string('icon', 191)->change();
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->string('icon', 191)->change();
        });

        Schema::table('awards', function (Blueprint $table) {
            $table->string('icon', 191)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon', 191)->change();
        });
    }

    public function down(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->string('icon', 60)->change();
        });

        Schema::table('medals', function (Blueprint $table) {
            $table->string('icon', 60)->change();
        });

        Schema::table('awards', function (Blueprint $table) {
            $table->string('icon', 60)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('icon')->change();
        });
    }
};
