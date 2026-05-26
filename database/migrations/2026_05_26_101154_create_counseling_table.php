<?php

use App\Enums\CounselingMethod;
use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
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
        Schema::create('counselings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'student_id');
            $table->foreignIdFor(User::class, 'counselor_id');
            $table->string('room')->nullable();
            $table->string('notes')->nullable();
            $table->string('reason')->nullable();
            $table->enum('type', ['internal', 'external'])->default('internal');
            $table->enum('resolution', CounselingResolution::cases())->nullable();
            $table->enum('method', CounselingMethod::cases())->nullable();
            $table->enum('status', CounselingStatus::cases())->default(CounselingStatus::DIJADWALKAN);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('cutdown_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling');
    }
};
