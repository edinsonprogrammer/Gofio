<?php

/**
 * Amplía el enum status de identity_verification_requests con el valor revoked.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE identity_verification_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'revoked') NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::table('identity_verification_requests')
            ->where('status', 'revoked')
            ->update(['status' => 'rejected']);

        DB::statement(
            "ALTER TABLE identity_verification_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'"
        );
    }
};
