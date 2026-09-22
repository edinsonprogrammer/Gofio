<?php

/**
 * Crea tablas de gamificación: medals, awards, award_categories y tablas pivote con usuarios.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->string('color', 7)->default('#65676B')->after('nombre');
            $table->string('icon', 60)->default('fa-solid fa-seedling')->after('color');
            $table->boolean('is_staff')->default(false)->after('limite_voto_diario');
            $table->boolean('auto_promote')->default(true)->after('is_staff');
        });

        Schema::create('medals', function (Blueprint $table) {
            $table->id();
            $table->string('title', 40);
            $table->string('description', 120)->default('');
            $table->string('icon', 60)->default('fa-solid fa-medal');
            $table->string('color', 7)->default('#F39C12');
            $table->string('condition_type', 20)->default('manual');
            $table->unsignedInteger('condition_value')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('medal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 120)->nullable();
            $table->dateTime('granted_at');
            $table->unique(['medal_id', 'user_id']);
        });

        Schema::create('award_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->default('');
            $table->string('icon', 60)->default('fa-solid fa-award');
            $table->string('color', 7)->default('#F39C12');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('award_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 120)->nullable();
            $table->dateTime('granted_at');
            $table->unique(['award_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_user');
        Schema::dropIfExists('awards');
        Schema::dropIfExists('award_categories');
        Schema::dropIfExists('medal_user');
        Schema::dropIfExists('medals');

        Schema::table('roles_rangos', function (Blueprint $table) {
            $table->dropColumn(['color', 'icon', 'is_staff', 'auto_promote']);
        });
    }
};
