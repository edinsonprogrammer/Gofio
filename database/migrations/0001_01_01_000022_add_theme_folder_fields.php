<?php

/**
 * Añade campos source, author y version a themes para temas basados en carpetas.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('source', 20)->default('database')->after('slug');
            $table->string('author', 80)->nullable()->after('description');
            $table->string('version', 20)->nullable()->after('author');
            $table->longText('custom_css')->nullable()->after('variables');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['source', 'author', 'version', 'custom_css']);
        });
    }
};
