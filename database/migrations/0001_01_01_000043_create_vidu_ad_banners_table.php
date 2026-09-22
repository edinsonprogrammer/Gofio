<?php

/**
 * Banners publicitarios verticales para la barra lateral derecha de Vidu Reels.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vidu_ad_banners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('image_path');
            $table->string('image_url');
            $table->string('link_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vidu_ad_banners');
    }
};
