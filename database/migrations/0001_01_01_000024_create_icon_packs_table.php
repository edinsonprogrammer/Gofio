<?php

/**
 * Crea la tabla icon_packs y añade icon_pack_slug a themes.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icon_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('source', 20)->default('folder');
            $table->string('description')->nullable();
            $table->string('author', 80)->nullable();
            $table->string('version', 20)->nullable();
            $table->json('mappings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->string('icon_pack_slug')->nullable()->after('custom_css');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('icon_pack_slug');
        });

        Schema::dropIfExists('icon_packs');
    }
};
