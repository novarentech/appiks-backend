<?php

use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sharings', function (Blueprint $table) {
            $table->enum('status', ReportStatus::cases())->after('priority')->default(ReportStatus::MENUNGGU_TINJAUAN->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sharings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
