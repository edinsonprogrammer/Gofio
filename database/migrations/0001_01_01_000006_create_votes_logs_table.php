<?php

/**
 * Crea la tabla votes_logs para auditar votos emitidos sobre posts y comentarios.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points_given');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'post_id', 'created_at']);
            $table->index(['user_id', 'comment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes_logs');
    }
};
