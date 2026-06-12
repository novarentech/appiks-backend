<?php

use App\Models\Counseling;
use App\Models\User;
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
        Schema::create('counseling_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Counseling::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'counselor_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_mode'); // CounselingMethod value (Tatap Muka, Video Call, Chat)
            $table->text('clinical_notes'); // Encrypted at Model level
            $table->string('resolution_status'); // CounselingResolution value
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_logs');
    }
};
