<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('counseling_consents', function (Blueprint $table) {
            if (!Schema::hasColumn('counseling_consents', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('rejected_at');
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE counseling_consents MODIFY COLUMN status ENUM('pending', 'granted', 'rejected', 'revoked') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counseling_consents', function (Blueprint $table) {
            if (Schema::hasColumn('counseling_consents', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE counseling_consents MODIFY COLUMN status ENUM('pending', 'granted', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
