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
        Schema::table('clinical_summaries', function (Blueprint $table) {
            $table->text('clinical_notes')->nullable()->after('raw_payload');
            $table->enum('rating', ['good', 'bad'])->nullable()->after('clinical_notes');
            $table->text('improvement_feedback')->nullable()->after('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinical_summaries', function (Blueprint $table) {
            $table->dropColumn(['clinical_notes', 'rating', 'improvement_feedback']);
        });
    }
};
