<?php

/**
 * Añade permisos de publicación por rango y metadatos adicionales a posts.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->json('post_permissions')->nullable()->after('auto_promote');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('tags', 128)->nullable()->after('content');
            $table->boolean('block_comments')->default(false)->after('tags');
            $table->boolean('is_private')->default(false)->after('block_comments');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['tags', 'block_comments', 'is_private']);
        });

        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->dropColumn('post_permissions');
        });
    }
};
