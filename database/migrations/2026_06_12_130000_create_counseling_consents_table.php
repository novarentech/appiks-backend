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
        Schema::create('counseling_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counseling_id')->constrained('counselings')->cascadeOnDelete();
            $table->enum('status', ['pending', 'granted', 'rejected'])->default('pending');
            $table->json('scopes')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_consents');
    }
};
