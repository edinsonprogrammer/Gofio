<?php

/**
 * Crea la tabla posts con contenido JSON, métricas y estado de publicación.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->longText('content');
            $table->integer('points_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->enum('status', ['draft', 'published', 'banned'])->default('published');
            $table->timestamps();

            $table->index(['status', 'points_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
