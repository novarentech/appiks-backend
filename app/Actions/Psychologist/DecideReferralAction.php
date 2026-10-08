<?php

namespace App\Actions\Psychologist;

use App\Actions\CreateBookingScheduleAction;
use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Jobs\GenerateGeminiReferralSummaryJob;
use App\Models\BookingSchedule;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DecideReferralAction
{
    public function __construct(
        protected CreateBookingScheduleAction $createBookingAction
    ) {}
    public function handle(BookingSchedule $booking, array $data): BookingSchedule
    {
        if ($data['action'] == 'confirm' && $booking->status !== BookingStatus::PENDING) {
            throw new UnprocessableEntityHttpException('Booking ini tidak lagi berstatus pending.');
        }

        return DB::transaction(function () use ($booking, $data) {
            $slot = $booking->slot;

            if ($data['action'] === 'confirm') {
                $booking->update(['status' => BookingStatus::CONFIRMED->value]);
                $slot->update(['status' => SlotStatus::CONFIRMED->value]);
                GenerateGeminiReferralSummaryJob::dispatch($booking->counseling);
            } elseif ($data['action'] === 'reschedule') {
                $booking->update([
                    'status' => BookingStatus::REJECTED->value,
                    'reject_reason' => $data['reschedule_reason']
                ]);
                $datas = [
                    "slot_id"=> $data['slot_id'],
                    "counseling_id"=> $booking->counseling->id
                ];
                $booking = $this->createBookingAction->handle($datas, $booking->counseling->student->id, BookingStatus::CONFIRMED->value);
            }

            return $booking->refresh()->load(['slot', 'student', 'counseling']);
        });
    }
}
