<?php

/**
 * Añade índices de rendimiento y contadores followers_count/following_count a users.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('followers_count')->default(0)->after('karma');
            $table->unsignedInteger('following_count')->default(0)->after('followers_count');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->index('following_id');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['status', 'is_private', 'category_id', 'created_at'], 'posts_feed_category_idx');
            $table->index(['status', 'is_private', 'is_sticky', 'is_featured', 'created_at'], 'posts_feed_sort_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'parent_id', 'points_count'], 'comments_post_thread_idx');
            $table->index(['user_id', 'created_at'], 'comments_user_daily_idx');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'posts_user_daily_idx');
        });

        DB::statement('
            UPDATE users u
            SET followers_count = (
                SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id
            ),
            following_count = (
                SELECT COUNT(*) FROM follows f WHERE f.follower_id = u.id
            )
        ');
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_user_daily_idx');
            $table->dropIndex('posts_feed_category_idx');
            $table->dropIndex('posts_feed_sort_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_thread_idx');
            $table->dropIndex('comments_user_daily_idx');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->dropIndex(['following_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['followers_count', 'following_count']);
        });
    }
};
