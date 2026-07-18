<?php

use App\Enums\SlotStatus;
use App\Models\PsychologistProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psychologist_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PsychologistProfile::class, 'psychologist_id')
                  ->constrained('psychologist_profiles')
                  ->cascadeOnDelete();
            $table->date('slot_date');
            $table->time('slot_start_time');
            $table->time('slot_end_time');
            $table->enum('status', array_column(SlotStatus::cases(), 'value'))
                  ->default(SlotStatus::AVAILABLE->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychologist_slots');
    }
};
