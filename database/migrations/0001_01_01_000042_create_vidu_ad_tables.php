<?php

/**
 * Pausas publicitarias Vidu: creativos de anuncio y override por video.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vidu_ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('video_path');
            $table->string('video_url');
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->timestamps();
        });

        Schema::table('vidu_videos', function (Blueprint $table) {
            // null = sigue la regla global; true/false = forzar pausa publicitaria
            $table->boolean('ads_override')->nullable()->after('is_private');
        });
    }

    public function down(): void
    {
        Schema::table('vidu_videos', function (Blueprint $table) {
            $table->dropColumn('ads_override');
        });

        Schema::dropIfExists('vidu_ad_creatives');
    }
};
