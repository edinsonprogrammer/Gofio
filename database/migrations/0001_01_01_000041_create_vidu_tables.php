<?php

/**
 * Crea las tablas del sistema Vidu: videos cortos, likes y guardados.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de videos Vidu
        Schema::create('vidu_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('video_path');
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('saves_count')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->enum('status', ['processing', 'active', 'banned'])->default('active');
            $table->boolean('is_private')->default(false);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        // Likes de videos Vidu
        Schema::create('vidu_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->references('id')->on('vidu_videos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });

        // Videos guardados por el usuario
        Schema::create('vidu_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->references('id')->on('vidu_videos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vidu_saves');
        Schema::dropIfExists('vidu_likes');
        Schema::dropIfExists('vidu_videos');
    }
};
