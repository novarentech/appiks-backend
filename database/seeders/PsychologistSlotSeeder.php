<?php

namespace Database\Seeders;

use App\Enums\SlotStatus;
use App\Models\PsychologistProfile;
use App\Models\PsychologistSlot;
use App\Models\User;
use Illuminate\Database\Seeder;

class PsychologistSlotSeeder extends Seeder
{
    public function run(): void
    {
        $username = 'sarah.wijaya@puskesmas-menteng.id';
        $user = User::where('username', $username)->first();

        if (!$user) {
            $this->command->warn('PsychologistSlotSeeder: Dr. Sarah not found. Run PsychologistSeeder first.');
            return;
        }

        $profile = PsychologistProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            $this->command->warn('PsychologistSlotSeeder: PsychologistProfile not found for Dr. Sarah.');
            return;
        }

        // Idempotency: skip if slots already exist for this profile on this date
        $slotDate = '2026-07-28';

        if (PsychologistSlot::where('psychologist_id', $profile->id)
                             ->where('slot_date', $slotDate)
                             ->exists()) {
            $this->command->info('PsychologistSlotSeeder: Slots already exist, skipping.');
            return;
        }

        $slots = [
            ['slot_start_time' => '08:00:00', 'slot_end_time' => '09:00:00'],
            ['slot_start_time' => '09:00:00', 'slot_end_time' => '10:00:00'],
            ['slot_start_time' => '10:00:00', 'slot_end_time' => '11:00:00'],
            ['slot_start_time' => '11:00:00', 'slot_end_time' => '12:00:00'],
        ];

        foreach ($slots as $slot) {
            PsychologistSlot::create([
                'psychologist_id' => $profile->id,
                'slot_date'       => $slotDate,
                'slot_start_time' => $slot['slot_start_time'],
                'slot_end_time'   => $slot['slot_end_time'],
                'status'          => SlotStatus::AVAILABLE->value,
            ]);
        }

        $this->command->info('PsychologistSlotSeeder: 4 slots seeded for 2026-07-28.');
    }
}
