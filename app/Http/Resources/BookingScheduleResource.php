<?php

namespace App\Http\Resources;

use App\Enums\BookingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        unset($data['deleted_at']);

        return array_merge($data, [
            'student'    => new UserResource($this->whenLoaded('student')),
            'counseling' => new CounselingResource($this->whenLoaded('counseling')),
            'slot'       => new PsychologistSlotResource($this->whenLoaded('slot')),
            // Diturunkan dari deadline_at karena cron expiry hanya jalan tiap 15 menit.
            'is_expired' => $this->status === BookingStatus::EXPIRED
                || ($this->status === BookingStatus::PENDING && $this->deadline_at?->isPast()),
        ]);
    }
}
