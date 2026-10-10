<?php

namespace Database\Seeders\Concerns;

use App\Enums\BookingStatus;
use App\Enums\CaseEventType;
use App\Enums\ConsentStatus;
use App\Enums\CounselingStatus;
use App\Models\CaseEvent;
use App\Models\Counseling;
use App\Models\Sharing;
use Illuminate\Support\Carbon;

/**
 * Membangun jejak kasus untuk data demo.
 *
 * Seeder menulis baris langsung ke tabel, bukan lewat Action, sehingga
 * perekam runtime tidak terpanggil dan timeline-nya akan kosong. Helper ini
 * menurunkan rantai kejadian dari fixture yang sudah dibuat — jadi tidak ada
 * daftar langkah yang ditulis dua kali dan isinya selalu konsisten dengan
 * data yang benar-benar ada.
 *
 * Khusus seeder: satu-satunya tempat di seluruh kode yang menghapus baris
 * case_events, yaitu menghapus baris yang dibuat CounselingObserver pada
 * waktu sekarang supaya tidak bentrok dengan timestamp historis di sini.
 */
trait SeedsCaseTimeline
{
    protected function seedCaseTimelineFor(Counseling $counseling): void
    {
        $counseling->loadMissing(['sharing.nlp', 'consents', 'bookingSchedule.slot', 'logs', 'clinicalSummary']);

        // Buang jejak otomatis dari observer (occurred_at = waktu seeding).
        CaseEvent::where('counseling_id', $counseling->id)->delete();

        $sharing = $counseling->sharing;
        $events  = [];

        // ── Tahap curhat & triage ──────────────────────────────────────────
        if ($sharing) {
            CaseEvent::where('sharing_id', $sharing->id)->delete();

            $events[] = [CaseEventType::SHARING_CREATED, $sharing->created_at, []];

            if ($nlp = $sharing->nlp) {
                $zone = $nlp->flag ?? ($nlp->response['zone_status'] ?? null);
                $events[] = [
                    CaseEventType::NLP_ANALYZED,
                    $sharing->created_at?->copy()->addSeconds(20),
                    array_filter([
                        'zone'        => $zone,
                        'priority'    => $sharing->priority,
                        'total_score' => $nlp->response['total_score'] ?? null,
                    ], fn ($v) => $v !== null),
                ];
            }

            if ($sharing->acknowledged_at) {
                $events[] = [CaseEventType::SHARING_ACKNOWLEDGED, $sharing->acknowledged_at, []];

                if ($sharing->action) {
                    $events[] = [
                        CaseEventType::FOLLOWUP_ACTION_CHOSEN,
                        $sharing->acknowledged_at->copy()->addMinutes(2),
                        ['action' => $sharing->action],
                    ];
                }
            }

            if ($sharing->replied_at) {
                // Kolomnya DATE, jadi jam dipilih agar tetap berurutan.
                $events[] = [
                    CaseEventType::SHARING_REPLIED,
                    Carbon::parse($sharing->replied_at)->setTime(9, 30),
                    [],
                ];
            }
        }

        // ── Tahap konseling ────────────────────────────────────────────────
        $events[] = [
            CaseEventType::COUNSELING_CREATED,
            $counseling->created_at,
            array_filter([
                'type'        => $counseling->type,
                'source_type' => $counseling->source_type,
            ]),
        ];

        if ($counseling->scheduled_at) {
            $events[] = [
                CaseEventType::COUNSELING_SCHEDULE_PROPOSED,
                $counseling->created_at?->copy()->addMinutes(5),
                ['scheduled_at' => $counseling->scheduled_at->toIso8601String()],
            ];
        }

        $events = array_merge($events, $this->consentEvents($counseling));
        $events = array_merge($events, $this->bookingEvents($counseling));

        foreach ($counseling->logs as $log) {
            $events[] = [
                CaseEventType::COUNSELING_LOG_STORED,
                $log->created_at,
                array_filter([
                    'resolution' => $log->resolution_status,
                    'method'     => $log->session_mode,
                ]),
            ];
        }

        if ($counseling->clinicalSummary && $counseling->clinicalSummary->summary_data !== '') {
            $events[] = [
                CaseEventType::AI_SUMMARY_GENERATED,
                $counseling->clinicalSummary->created_at,
                [],
            ];
        }

        $events = array_merge($events, $this->terminalEvents($counseling));

        $this->insertTimeline($counseling, $sharing, $events);
    }

    /** @return array<int, array{0: CaseEventType, 1: mixed, 2: array<string, mixed>}> */
    private function consentEvents(Counseling $counseling): array
    {
        $events = [];

        foreach ($counseling->consents as $consent) {
            $events[] = [CaseEventType::CONSENT_REQUESTED, $consent->created_at, []];

            if ($consent->status === ConsentStatus::GRANTED && $consent->granted_at) {
                $scopes = $consent->scopes ?? [];
                $events[] = [CaseEventType::CONSENT_GRANTED, $consent->granted_at, [
                    'scopes'       => $scopes,
                    'scopes_count' => count($scopes),
                ]];
            }

            if ($consent->status === ConsentStatus::REJECTED && $consent->rejected_at) {
                $events[] = [CaseEventType::CONSENT_REJECTED, $consent->rejected_at, []];
            }
        }

        return $events;
    }

    /** @return array<int, array{0: CaseEventType, 1: mixed, 2: array<string, mixed>}> */
    private function bookingEvents(Counseling $counseling): array
    {
        $events = [];

        foreach ($counseling->bookingSchedule as $booking) {
            $slotMeta = array_filter([
                'slot_date' => $booking->slot?->slot_date?->toDateString(),
                'slot_time' => $booking->slot?->slot_start_time?->format('H:i'),
            ]);

            $events[] = [
                CaseEventType::BOOKING_CREATED,
                $booking->created_at,
                $slotMeta + ['deadline_at' => $booking->deadline_at?->toIso8601String()],
            ];

            $decidedAt = $booking->updated_at ?? $booking->created_at;

            $events[] = match ($booking->status) {
                BookingStatus::CONFIRMED, BookingStatus::FINISHED
                    => [CaseEventType::BOOKING_CONFIRMED, $decidedAt, $slotMeta],
                BookingStatus::RESCHEDULED
                    => [CaseEventType::BOOKING_RESCHEDULED, $decidedAt, array_filter(['reason' => $booking->reject_reason])],
                BookingStatus::REJECTED
                    => [CaseEventType::BOOKING_REJECTED, $decidedAt, array_filter(['reason' => $booking->reject_reason])],
                BookingStatus::EXPIRED
                    => [CaseEventType::BOOKING_EXPIRED, $booking->deadline_at ?? $decidedAt, ['deadline_at' => $booking->deadline_at?->toIso8601String()]],
                default => null,
            };
        }

        return array_values(array_filter($events));
    }

    /** @return array<int, array{0: CaseEventType, 1: mixed, 2: array<string, mixed>}> */
    private function terminalEvents(Counseling $counseling): array
    {
        return match ($counseling->status) {
            CounselingStatus::SELESAI    => [[CaseEventType::CASE_CLOSED, $counseling->updated_at, []]],
            CounselingStatus::DITOLAK    => [[CaseEventType::COUNSELING_DECLINED, $counseling->updated_at, []]],
            CounselingStatus::DIBATALKAN => [[CaseEventType::COUNSELING_CANCELLED, $counseling->updated_at, []]],
            CounselingStatus::DIJADWALKAN, CounselingStatus::DIJADWAL_ULANG
                => [[CaseEventType::COUNSELING_ACCEPTED, $counseling->updated_at, []]],
            default => [],
        };
    }

    /**
     * @param  array<int, array{0: CaseEventType, 1: mixed, 2: array<string, mixed>}>  $events
     */
    private function insertTimeline(Counseling $counseling, ?Sharing $sharing, array $events): void
    {
        $events = array_values(array_filter($events, fn ($e) => $e !== null && $e[1] !== null));

        usort($events, fn ($a, $b) => Carbon::parse($a[1])->timestamp <=> Carbon::parse($b[1])->timestamp);

        foreach ($events as [$type, $at, $payload]) {
            CaseEvent::create([
                'sharing_id'    => $sharing?->id,
                'counseling_id' => $counseling->id,
                'event'         => $type->value,
                'actor_id'      => null,
                'actor_role'    => null,
                'occurred_at'   => Carbon::parse($at),
                'payload'       => $payload === [] ? null : $payload,
            ]);
        }
    }
}
