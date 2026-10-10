<?php

namespace Database\Seeders;

use App\Models\CaseEvent;
use App\Models\Counseling;
use Database\Seeders\Concerns\SeedsCaseTimeline;
use Illuminate\Database\Seeder;

/**
 * Membangun jejak kasus untuk seluruh data demo.
 *
 * Dijalankan paling akhir, setelah semua fixture ada, karena jejaknya
 * diturunkan dari timestamp record yang sudah terbentuk. Idempoten: helper-nya
 * menghapus jejak lama sebuah kasus sebelum menulis ulang.
 */
class CaseTimelineSeeder extends Seeder
{
    use SeedsCaseTimeline;

    public function run(): void
    {
        $counselings = Counseling::with(['sharing.nlp', 'consents', 'bookingSchedule.slot', 'logs', 'clinicalSummary'])->get();

        foreach ($counselings as $counseling) {
            $this->seedCaseTimelineFor($counseling);
        }

        $this->command->info(
            'CaseTimelineSeeder: jejak dibangun untuk '.$counselings->count().' kasus, '
            .CaseEvent::count().' kejadian total.'
        );
    }
}
