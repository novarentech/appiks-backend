<?php

use App\Enums\BookingStatus;
use App\Models\Counseling;
use App\Models\PsychologistSlot;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Counseling::class, 'counseling_id')
                  ->constrained('counselings')
                  ->cascadeOnDelete();
            $table->foreignIdFor(PsychologistSlot::class, 'slot_id')
                  ->constrained('psychologist_slots')
                  ->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'student_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->enum('status', array_column(BookingStatus::cases(), 'value'))
                  ->default(BookingStatus::PENDING->value);
            $table->dateTime('deadline_at');
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_schedules');
    }
};
