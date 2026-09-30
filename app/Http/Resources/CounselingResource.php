<?php

namespace App\Http\Resources;

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

        return array_merge(parent::toArray($request), [
            'room' => $room,
            'student' => new UserResource($this->whenLoaded('student')),
            'counselor' => new UserResource($this->whenLoaded('counselor')),
            'sharing' => new SharingResource($this->whenLoaded('sharing')),
            'psychologist' => new UserResource($this->whenLoaded('psychologist')),
        ]);
    }
}
