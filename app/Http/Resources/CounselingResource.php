<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use App\Models\BookingSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounselingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $room = $this->room;
        if ($this->type == 'external' && $this->relationLoaded('psychologist') && $this->psychologist?->relationLoaded('psychologistProfile')) {
            $room = $this->psychologist->psychologistProfile?->institution_name ?? $room;
        }


        $data = parent::toArray($request);
        unset($data['latest_booking_schedule']);
        // Hanya penolong internal agar `was_rescheduled` tidak memicu N+1;
        // bukan bagian kontrak API.
        unset($data['rescheduled_bookings_count']);
        // if ($this->relationLoaded('clinicalSummary')) {
        //     unset($data['clinical_summary']['raw_payload']);
        // }

        return array_merge($data, [
            'room' => $room,
            'student' => new UserResource($this->whenLoaded('student')),
            'counselor' => new UserResource($this->whenLoaded('counselor')),
            'sharing' => new SharingResource($this->whenLoaded('sharing')),
            'psychologist' => new UserResource($this->whenLoaded('psychologist')),
            'clinical_summary' => new ClinicalSummaryResource($this->whenLoaded('clinicalSummary')),
            'slot' => $this->whenLoaded('latestBookingSchedule', fn () => $this->latestBookingSchedule?->slot),
            'latest_booking' => $this->whenLoaded(
                'latestBookingSchedule',
                fn () => $this->formatBooking($this->latestBookingSchedule)
            ),
        ]);
    }

    /**
     * Ringkasan booking terbaru. Tanpa ini frontend tidak punya cara mengetahui
     * jadwal rujukannya sudah kadaluarsa, karena id booking hanya pernah
     * dikembalikan sekali saat booking dibuat.
     *
     * @return array<string, mixed>|null
     */
    private function formatBooking(?BookingSchedule $booking): ?array
    {
        if (! $booking) {
            return null;
        }

        return [
            'id'              => $booking->id,
            'status'          => $booking->status?->value,
            'deadline_at'     => $booking->deadline_at?->toIso8601String(),
            'reject_reason'   => $booking->reject_reason,
            'location'        => $booking->location,
            'is_expired'      => $this->isExpired($booking),
            'was_rescheduled' => $this->wasRescheduled(),
        ];
    }

    /**
     * Command `referrals:expire-pending` hanya berjalan tiap 15 menit, jadi ada
     * jendela di mana booking sudah melewati tenggat tapi belum ditandai.
     * Turunkan dari `deadline_at` agar UI tidak pernah menampilkan tenggat yang
     * sudah lewat sebagai masih aktif.
     */
    private function isExpired(BookingSchedule $booking): bool
    {
        if ($booking->status === BookingStatus::EXPIRED) {
            return true;
        }

        return $booking->status === BookingStatus::PENDING
            && $booking->deadline_at !== null
            && $booking->deadline_at->isPast();
    }

    /**
     * Sumber badge "Perubahan Jadwal": ada booking sebelumnya pada rujukan yang
     * sama yang digeser psikolog. Reschedule bersifat auto-setuju sehingga tidak
     * punya status tersendiri di level counseling.
     */
    private function wasRescheduled(): bool
    {
        // Disediakan lewat withCount pada endpoint yang mengembalikan koleksi,
        // supaya tidak memicu N+1.
        if (isset($this->resource->rescheduled_bookings_count)) {
            return $this->resource->rescheduled_bookings_count > 0;
        }

        if ($this->relationLoaded('bookingSchedule')) {
            return $this->bookingSchedule
                ->contains(fn (BookingSchedule $booking) => $booking->status === BookingStatus::RESCHEDULED);
        }

        return $this->bookingSchedule()
            ->where('status', BookingStatus::RESCHEDULED->value)
            ->exists();
    }
}
