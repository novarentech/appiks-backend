<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\CounselingStatus;
use App\Enums\SlotStatus;
use App\Events\BookingExpired;
use App\Models\BookingSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingReferrals extends Command
{
    protected $signature = 'referrals:expire-pending';
    protected $description = 'Expire pending referrals that have passed their 24-hour SLA';

    public function handle()
    {
        $expiredBookings = BookingSchedule::expired()->with(['slot', 'counseling'])->get();
        $count = 0;

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking, &$count) {
                // Change booking to expired
                $booking->update(['status' => BookingStatus::EXPIRED->value]);

                // Revert slot back to available
                if ($booking->slot) {
                    $booking->slot->update(['status' => SlotStatus::AVAILABLE->value]);
                }

                // Rujukannya sendiri tetap hidup: siswa dikembalikan ke tahap
                // memilih jadwal, bukan kasusnya ditutup.
                $counseling = $booking->counseling;

                if ($counseling && $counseling->status->isActive()) {
                    $counseling->update(['status' => CounselingStatus::MENUNGGU_JADWAL->value]);
                }

                // Dispatch notification event
                BookingExpired::dispatch($booking);
                $count++;
            });
        }

        $this->info("Successfully expired {$count} pending referrals.");
        Log::info("ExpirePendingReferrals Command ran. Expired: {$count}");
    }
}
