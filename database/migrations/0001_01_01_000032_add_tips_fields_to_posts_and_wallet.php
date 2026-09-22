<?php

/**
 * Añade tips_total y tips_count a posts, y post_id a wallet_transactions.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->decimal('tips_total', 12, 2)->default(0)->after('views_count');
            $table->unsignedInteger('tips_count')->default(0)->after('tips_total');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreignId('post_id')->nullable()->after('receiver_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_id');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['tips_total', 'tips_count']);
        });
    }
};
