<?php

/**
 * Crea profile_visits y añade is_creator_plus_priority a support_tickets.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['profile_user_id', 'visited_at']);
            $table->index(['visitor_id', 'visited_at']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->boolean('is_creator_plus_priority')->default(false)->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('is_creator_plus_priority');
        });

        Schema::dropIfExists('profile_visits');
    }
};
