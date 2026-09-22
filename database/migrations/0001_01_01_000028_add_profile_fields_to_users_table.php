<?php

/**
 * Añade campos de perfil social: avatar, banner, país, edad, bio y redes.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country', 80)->nullable()->after('banner_url');
            $table->char('country_code', 2)->nullable()->after('country');
            $table->unsignedTinyInteger('age')->nullable()->after('country_code');
            $table->string('bio', 300)->nullable()->after('age');
            $table->string('whatsapp', 20)->nullable()->after('bio');
            $table->string('instagram', 100)->nullable()->after('whatsapp');
            $table->string('facebook', 100)->nullable()->after('instagram');
            $table->string('social_x', 100)->nullable()->after('facebook');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'country_code',
                'age',
                'bio',
                'whatsapp',
                'instagram',
                'facebook',
                'social_x',
            ]);
        });
    }
};
