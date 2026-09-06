<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingScheduleAction;
use App\Actions\GetAvailableDatesAction;
use App\Actions\GetAvailableSlotsAction;
use App\Http\Requests\StoreBookingRequest;
use App\Models\BookingSchedule;
use App\Models\Counseling;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentBookingController extends Controller
{
    use ApiResponder;

    /**
     * Get available dates
     *
     * Retrieve available consultation dates for a counseling referral.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     psychologist: array{
     *       name: string,
     *       facility_name: string,
     *       specialization: string
     *     },
     *     earliest_available_date: string|null,
     *     available_dates: array<array{
     *       date_raw: string,
     *       date_formatted: string,
     *       available_slots_count: int,
     *       slot_label: string,
     *       is_selectable: bool
     *     }>
     *   }
     * }
     */
    public function availableDates(Counseling $counseling, GetAvailableDatesAction $action): JsonResponse
    {
        // Ownership guard: student must own this counseling
        abort_if($counseling->student_id !== auth()->id(), 403);

        $profile = $counseling->psychologist->psychologistProfile;
        $data    = $action->handle($counseling);

        return $this->success(
            array_merge([
                'psychologist' => [
                    'name'           => $counseling->psychologist->name,
                    'facility_name'  => $profile->institution_name,
                    'specialization' => $profile->specialization,
                ],
            ], $data),
            'Available consultation dates retrieved.'
        );
    }

    /**
     * Get available slots
     *
     * Retrieve available time slots on a specific date for a counseling referral.
     */
    public function availableSlots(Request $request, Counseling $counseling, GetAvailableSlotsAction $action): JsonResponse
    {
        abort_if($counseling->student_id !== auth()->id(), 403);

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $data = $action->handle($counseling, $request->date);

        return $this->success($data, 'Available time slots retrieved.');
    }

    /**
     * Submit a booking request
     *
     * Propose a consultation booking schedule for the student referral.
     */
    public function store(StoreBookingRequest $request, CreateBookingScheduleAction $action): JsonResponse
    {
        $counseling = Counseling::findOrFail($request->counseling_id);
        abort_if($counseling->student_id !== auth()->id(), 403);
        abort_if($counseling->type !== 'external', 403);

        $booking = $action->handle($request->validated(), auth()->id());

        $slot  = $booking->slot;
        $start = Carbon::parse($slot->slot_start_time)->format('H:i');
        $end   = Carbon::parse($slot->slot_end_time)->format('H:i');

        return $this->success([
            'booking_id'  => $booking->id,
            'status'      => $booking->status,
            'deadline_at' => $booking->deadline_at->setTimezone('Asia/Jakarta')->toIso8601String(),
            'slot'        => [
                'date'       => $slot->slot_date->toDateString(),
                'time_range' => "{$start} - {$end} WIB",
            ],
        ], 'Jadwal berhasil diajukan. Menunggu konfirmasi psikolog.');
    }

    /**
     * Get booking details
     *
     * Retrieve details of a specific booking schedule.
     */
    public function show(BookingSchedule $booking): JsonResponse
    {
        abort_if($booking->student_id !== auth()->id(), 403);

        $booking->load(['slot', 'counseling.psychologist.psychologistProfile', 'counseling.counselor']);

        $slot      = $booking->slot;
        $counseling = $booking->counseling;
        $profile   = $counseling->psychologist->psychologistProfile;
        $start     = Carbon::parse($slot->slot_start_time)->format('H:i');
        $end       = Carbon::parse($slot->slot_end_time)->format('H:i');

        return $this->success([
            'booking_id'             => $booking->id,
            'status'                 => $booking->status,
            'psychologist_name'      => $counseling->psychologist->name,
            'facility_name'          => $profile->institution_name,
            'counselor_name'         => $counseling->counselor->name,
            'time_slot_label'        => "{$start} - {$end} WIB",
            'date_formatted'         => Carbon::parse($slot->slot_date)->locale('id')->translatedFormat('l, j F Y'),
            'location'               => $booking->location,
            'deadline_at'            => $booking->deadline_at->setTimezone('Asia/Jakarta')->toIso8601String(),
            'created_at_formatted'   => $booking->created_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('l, j F Y, H:i \W\I\B'),
        ], 'Booking detail retrieved.');
    }
}
