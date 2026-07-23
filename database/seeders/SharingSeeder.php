<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Sharing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SharingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $statuses = ReportStatus::cases();
        $statusCount = count($statuses);
        $all = [];
        $index = 0;

        foreach ($students as $student) {
            // 3 tanggal unik acak dalam 35 hari terakhir
            $dates = collect(range(0, 35))
                ->map(fn($i) => Carbon::yesterday()->subDays($i))
                ->shuffle()
                ->take(3)
                ->values();

            foreach ($dates as $date) {
                $attrs = Sharing::factory()->raw();
                $currentStatus = $statuses[$index % $statusCount];
                $index++;

                $attrs['user_id'] = $student->id;
                $attrs['status']  = $currentStatus->value;

                // Determine if reply should be present based on status
                $hasReply = in_array($currentStatus, [
                    ReportStatus::DITINJAU,
                    ReportStatus::MENUNGGU_PERSETUJUAN,
                    ReportStatus::DIJADWALKAN,
                    ReportStatus::SELESAI,
                    ReportStatus::DITOLAK,
                ]);

                if ($hasReply) {
                    $attrs['replied_at'] = Carbon::parse($date)->format('Y-m-d');
                    $attrs['replied_by'] = $student->counselor?->name ?? 'Guru BK';
                } else {
                    $attrs['reply']      = null;
                    $attrs['replied_at'] = null;
                    $attrs['replied_by'] = null;
                }

                $attrs['created_at'] = Carbon::parse($date)->format('Y-m-d H:i:s');
                $attrs['updated_at'] = Carbon::parse($date)->format('Y-m-d H:i:s');

                $all[] = $attrs;
            }
        }

        // insert dalam chunk (aman jika datanya besar)
        DB::table('sharings')->insert($all);
    }
}
