<?php

use App\Enums\NlpAnalysisStatus;
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
        Schema::create('nlp_analyses', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->json('response')->nullable();
            $table->string('flag')->nullable();
            $table->enum('status', NlpAnalysisStatus::cases())->nullable();
            $table->string('reason')->nullable();
            $table->nullableMorphs('nlpable');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nlp_analyses');
    }
};
