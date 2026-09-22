<?php

/**
 * Añade seen_at a app_notifications para distinguir vista de lectura.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable()->after('read_at');
            $table->index(['user_id', 'seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'seen_at']);
            $table->dropColumn('seen_at');
        });
    }
};
