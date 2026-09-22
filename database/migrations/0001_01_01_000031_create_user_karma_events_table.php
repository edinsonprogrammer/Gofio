<?php

/**
 * Crea la tabla user_karma_events para auditar cambios de karma.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_karma_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key', 64);
            $table->string('reference_type', 32)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->integer('karma_awarded');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'rule_key', 'reference_type', 'reference_id'], 'user_karma_events_unique');
            $table->index(['user_id', 'rule_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_karma_events');
    }
};
