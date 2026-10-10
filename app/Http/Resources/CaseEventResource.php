<?php

namespace App\Http\Resources;

use App\Support\CaseTimelineVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseEventResource extends JsonResource
{
    /**
     * Satu langkah pada timeline. Penyaringan memakai peran pemanggil, bukan
     * peran yang disimpan di baris event — jadi satu tabel bisa melayani
     * beberapa peran dengan tingkat kedalaman berbeda.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()?->role;

        $data = [
            'id'          => $this->id,
            'event'       => $this->event->value,
            'label'       => $this->event->label(),
            'stage'       => $this->event->stage(),
            'is_system'   => $this->event->isSystemEvent(),
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'payload'     => CaseTimelineVisibility::filterPayload($role, $this->payload),
        ];

        if (CaseTimelineVisibility::canSeeActor($role)) {
            $data['actor'] = $this->actor_id === null ? null : [
                'id'   => $this->actor_id,
                'name' => $this->whenLoaded('actor', fn () => $this->actor?->name),
                'role' => $this->actor_role,
            ];
        }

        return $data;
    }
}
