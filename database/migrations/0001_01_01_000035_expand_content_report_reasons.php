<?php

/**
 * Amplía los motivos de denuncia disponibles en content_reports.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_reports', function (Blueprint $table) {
            $table->string('reason', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_reports', function (Blueprint $table) {
            $table->enum('reason', ['spam', 'harassment', 'illegal', 'copyright', 'other'])->change();
        });
    }
};
