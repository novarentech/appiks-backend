<?php

namespace App\Http\Resources;

use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusMap = [
            'menunggu' => ReportStatus::MENUNGGU_TINJAUAN->value,
            'menunggu_jadwal' => ReportStatus::MENUNGGU_TINJAUAN->value,
            'menunggu_konfirmasi' => ReportStatus::MENUNGGU_TINJAUAN->value,
            'dijadwalkan' => ReportStatus::DIJADWALKAN->value,
            'dijadwal_ulang' => ReportStatus::DIJADWALKAN->value,
            'selesai' => ReportStatus::SELESAI->value,
            'ditolak' => ReportStatus::DITOLAK->value,
            'dibatalkan' => ReportStatus::DIBATALKAN->value,
            'menunggu_diskusi' => ReportStatus::MENUNGGU_DISKUSI->value,
        ];

        $rawStatus = $this->status instanceof CounselingStatus ? $this->status->value : $this->status;
        $status = $statusMap[$rawStatus] ?? ($rawStatus ?? ReportStatus::MENUNGGU_TINJAUAN->value);

        $date = null;
        $time = null;
        if (!empty($this->scheduled_at)) {
            $parsed = Carbon::parse($this->scheduled_at);
            $date = $parsed->toDateString();
            $time = $parsed->format('H:i');
        } elseif (!empty($this->date)) {
            $date = $this->date;
            $time = $this->time ?? null;
        }

        $studentRelation = $this->relationLoaded('student') ? $this->student : ($this->relationLoaded('user') ? $this->user : null);
        $counselorRelation = $this->relationLoaded('counselor') ? $this->counselor : null;

        $data = parent::toArray($request);

        return array_merge($data, [
            'id' => $this->id,
            'user_id' => $this->student_id ?? $this->user_id,
            'counselor_id' => $this->counselor_id,
            'topic' => $this->reason ?? $this->topic,
            'room' => $this->room,
            'date' => $date,
            'time' => $time,
            'status' => $status,
            'priority' => $this->priority ?? 'rendah',
            'notes' => $this->notes,
            'result' => $this->result,
            'user' => $studentRelation ? new UserResource($studentRelation) : $this->whenLoaded('user'),
            'counselor' => $counselorRelation ? new UserResource($counselorRelation) : $this->whenLoaded('counselor'),
        ]);
    }
}
