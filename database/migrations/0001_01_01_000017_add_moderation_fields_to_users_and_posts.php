<?php

/**
 * Añade campos de moderación (baneo, destacado, sticky) a users y posts.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('is_admin');
            $table->dateTime('banned_until')->nullable()->after('is_banned');
            $table->text('ban_reason')->nullable()->after('banned_until');
            $table->boolean('is_active')->default(true)->after('ban_reason');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('is_sticky')->default(false)->after('is_featured');
            $table->dateTime('featured_at')->nullable()->after('is_sticky');
            $table->foreignId('featured_by')->nullable()->after('featured_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('moderation_alerts', function (Blueprint $table) {
            $table->enum('status', ['open', 'resolved', 'dismissed'])->default('open')->after('metadata');
            $table->foreignId('resolved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['status', 'resolved_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_by');
            $table->dropColumn(['is_featured', 'is_sticky', 'featured_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_banned', 'banned_until', 'ban_reason', 'is_active']);
        });
    }
};
