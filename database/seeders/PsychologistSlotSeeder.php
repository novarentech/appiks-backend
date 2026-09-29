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

        // Generate Monday and Wednesday dates for 1 month starting from current week
        $dates = [];
        $cursor = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->addMonth();

        while ($cursor->lte($endDate)) {
            if ($cursor->isMonday() || $cursor->isWednesday()) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

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

        $this->command->info('PsychologistSlotSeeder: Monday and Wednesday 08:00-11:00 slots (3 slots/day for 1 month) seeded successfully.');
    }
}
