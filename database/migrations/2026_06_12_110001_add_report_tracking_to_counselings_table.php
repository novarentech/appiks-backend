<?php

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
        Schema::table('counselings', function (Blueprint $table) {
            $table->enum('source_type', ['regular', 'nlp_incident'])->default('regular')->after('id');
            $table->foreignIdFor(\App\Models\Report::class, 'report_id')->nullable()->constrained()->nullOnDelete()->after('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counselings', function (Blueprint $table) {
            $table->dropForeign(['report_id']);
            $table->dropColumn(['source_type', 'report_id']);
        });
    }
};
