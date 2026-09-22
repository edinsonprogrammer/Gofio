<?php

/**
 * Añade índices compuestos en votes_logs, posts y messages para consultas frecuentes.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'votes_logs_user_created_idx');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'posts_user_created_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'id'], 'messages_conversation_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_id_idx');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_user_created_idx');
        });

        Schema::table('votes_logs', function (Blueprint $table) {
            $table->dropIndex('votes_logs_user_created_idx');
        });
    }
};
