<?php

namespace Database\Seeders;

use App\Enums\SlotStatus;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PsychologistSlotSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = PsychologistProfile::all();

        if ($profiles->isEmpty()) {
            $this->command->warn('PsychologistSlotSeeder: No PsychologistProfile found. Run PsychologistSeeder first.');
            return;
        }

        // Define Monday and Wednesday dates for next 4 weeks
        $dates = [
            '2026-07-28', // fixed historical date for existing tests
            Carbon::now()->startOfWeek()->toDateString(), // current Monday
            Carbon::now()->startOfWeek()->addDays(2)->toDateString(), // current Wednesday
            Carbon::now()->startOfWeek()->addWeek()->toDateString(), // next Monday
            Carbon::now()->startOfWeek()->addWeek()->addDays(2)->toDateString(), // next Wednesday
            Carbon::now()->startOfWeek()->addWeeks(2)->toDateString(), // 2 weeks Monday
            Carbon::now()->startOfWeek()->addWeeks(2)->addDays(2)->toDateString(), // 2 weeks Wednesday
        ];

        $timeSlots = [
            ['slot_start_time' => '08:00:00', 'slot_end_time' => '09:00:00'],
            ['slot_start_time' => '09:00:00', 'slot_end_time' => '10:00:00'],
            ['slot_start_time' => '10:00:00', 'slot_end_time' => '11:00:00'],
        ];

        foreach ($profiles as $profile) {
            foreach ($dates as $slotDate) {
                foreach ($timeSlots as $slot) {
                    PsychologistSlot::firstOrCreate(
                        [
                            'psychologist_id' => $profile->id,
                            'slot_date'       => $slotDate,
                            'slot_start_time' => $slot['slot_start_time'],
                        ],
                        [
                            'slot_end_time'   => $slot['slot_end_time'],
                            'status'          => SlotStatus::AVAILABLE->value,
                        ]
                    );
                }
            }
        }

        $this->command->info('PsychologistSlotSeeder: Monday and Wednesday 08:00-11:00 slots seeded successfully.');
    }
}
