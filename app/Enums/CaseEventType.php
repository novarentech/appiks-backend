<?php

namespace App\Enums;

/**
 * Jenis kejadian pada siklus sebuah kasus, dari curhat dibuat sampai kasus ditutup.
 *
 * Nilainya disimpan sebagai string di `case_events.event` — sengaja string, bukan
 * enum MySQL, supaya menambah jenis kejadian baru tidak butuh ALTER TABLE.
 */
enum CaseEventType: string
{
    // ── Tahap curhat & triage ────────────────────────────────────────────────
    case SHARING_CREATED              = 'sharing_created';
    case NLP_ANALYZED                 = 'nlp_analyzed';
    case NLP_ANALYSIS_FAILED          = 'nlp_analysis_failed';
    case PRIORITY_ESCALATED           = 'priority_escalated';
    case SLA_DEADLINE_ELAPSED         = 'sla_deadline_elapsed';
    case SHARING_ACKNOWLEDGED         = 'sharing_acknowledged';
    case FOLLOWUP_ACTION_CHOSEN       = 'followup_action_chosen';
    case SHARING_REPLIED              = 'sharing_replied';
    case SHARING_MARKED_FALSE_POSITIVE = 'sharing_marked_false_positive';

    // ── Tahap konseling ─────────────────────────────────────────────────────
    case COUNSELING_CREATED           = 'counseling_created';
    case COUNSELING_SCHEDULE_PROPOSED = 'counseling_schedule_proposed';
    case COUNSELING_ACCEPTED          = 'counseling_accepted';
    case COUNSELING_DECLINED          = 'counseling_declined';
    case COUNSELING_REPROPOSED        = 'counseling_reproposed';
    case COUNSELING_CANCELLED         = 'counseling_cancelled';
    case COUNSELING_LOG_STORED        = 'counseling_log_stored';

    // ── Tahap rujukan psikolog ──────────────────────────────────────────────
    case CONSENT_REQUESTED            = 'consent_requested';
    case CONSENT_GRANTED              = 'consent_granted';
    case CONSENT_REJECTED             = 'consent_rejected';
    case BOOKING_CREATED              = 'booking_created';
    case BOOKING_CONFIRMED            = 'booking_confirmed';
    case BOOKING_RESCHEDULED          = 'booking_rescheduled';
    case BOOKING_REJECTED             = 'booking_rejected';
    case BOOKING_EXPIRED              = 'booking_expired';
    case AI_SUMMARY_GENERATED         = 'ai_summary_generated';
    case AI_SUMMARY_FAILED            = 'ai_summary_failed';
    case CASE_CLOSED                  = 'case_closed';

    /**
     * Label siap tampil, mengikuti gaya label timeline di desain
     * ("Kasus terdeteksi", "Guru BK mulai menangani", "Konseling selesai, kasus ditutup").
     */
    public function label(): string
    {
        return match ($this) {
            self::SHARING_CREATED               => 'Curhat dikirim siswa',
            self::NLP_ANALYZED                  => 'Kasus terdeteksi sistem',
            self::NLP_ANALYSIS_FAILED           => 'Analisis risiko gagal dijalankan',
            self::PRIORITY_ESCALATED            => 'Prioritas dinaikkan',
            self::SLA_DEADLINE_ELAPSED          => 'Batas waktu tindak lanjut terlampaui',
            self::SHARING_ACKNOWLEDGED          => 'Guru BK mulai menangani',
            self::FOLLOWUP_ACTION_CHOSEN        => 'Keputusan tindak lanjut dipilih',
            self::SHARING_REPLIED               => 'Guru BK membalas curhat',
            self::SHARING_MARKED_FALSE_POSITIVE => 'Alert ditandai bukan urgent',

            self::COUNSELING_CREATED            => 'Sesi konseling dibuat',
            self::COUNSELING_SCHEDULE_PROPOSED  => 'Jadwal konseling diajukan ke siswa',
            self::COUNSELING_ACCEPTED           => 'Siswa menyetujui jadwal',
            self::COUNSELING_DECLINED           => 'Siswa menolak jadwal',
            self::COUNSELING_REPROPOSED         => 'Jadwal konseling diajukan ulang',
            self::COUNSELING_CANCELLED          => 'Sesi konseling dibatalkan',
            self::COUNSELING_LOG_STORED         => 'Hasil konseling dicatat',

            self::CONSENT_REQUESTED             => 'Persetujuan akses data diminta',
            self::CONSENT_GRANTED               => 'Siswa menyetujui akses data',
            self::CONSENT_REJECTED              => 'Siswa menolak akses data',
            self::BOOKING_CREATED               => 'Siswa mengajukan jadwal konsultasi',
            self::BOOKING_CONFIRMED             => 'Psikolog mengonfirmasi jadwal',
            self::BOOKING_RESCHEDULED           => 'Psikolog menggeser jadwal',
            self::BOOKING_REJECTED              => 'Psikolog menolak rujukan',
            self::BOOKING_EXPIRED               => 'Jadwal konsultasi kedaluwarsa',
            self::AI_SUMMARY_GENERATED          => 'Ringkasan klinis AI dibuat',
            self::AI_SUMMARY_FAILED             => 'Ringkasan klinis AI gagal dibuat',
            self::CASE_CLOSED                   => 'Konseling selesai, kasus ditutup',
        };
    }

    /**
     * Tahap siklus, untuk mengelompokkan timeline di UI.
     */
    public function stage(): string
    {
        return match ($this) {
            self::SHARING_CREATED, self::NLP_ANALYZED, self::NLP_ANALYSIS_FAILED,
            self::PRIORITY_ESCALATED, self::SLA_DEADLINE_ELAPSED, self::SHARING_ACKNOWLEDGED,
            self::FOLLOWUP_ACTION_CHOSEN, self::SHARING_REPLIED,
            self::SHARING_MARKED_FALSE_POSITIVE => 'triage',

            self::COUNSELING_CREATED, self::COUNSELING_SCHEDULE_PROPOSED,
            self::COUNSELING_ACCEPTED, self::COUNSELING_DECLINED,
            self::COUNSELING_REPROPOSED, self::COUNSELING_CANCELLED,
            self::COUNSELING_LOG_STORED => 'konseling',

            default => 'rujukan',
        };
    }

    /**
     * Kejadian yang dipicu sistem, bukan oleh seorang pengguna.
     * Dipakai UI untuk tidak menampilkan nama aktor pada baris ini.
     */
    public function isSystemEvent(): bool
    {
        return match ($this) {
            self::NLP_ANALYZED, self::NLP_ANALYSIS_FAILED, self::PRIORITY_ESCALATED,
            self::SLA_DEADLINE_ELAPSED, self::BOOKING_EXPIRED,
            self::AI_SUMMARY_GENERATED, self::AI_SUMMARY_FAILED => true,
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
