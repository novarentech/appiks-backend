<?php

namespace App\Support;

use App\Enums\CaseEventType;
use App\Enums\UserRole;

/**
 * Siapa boleh melihat jejak kasus, dan field apa saja yang ikut terkirim.
 *
 * Saat ini hanya Kepala Sekolah yang diberi akses. Tabel RULES di bawah adalah
 * satu-satunya tempat yang perlu disentuh untuk membuka akses peran lain:
 * tambahkan satu entri, lalu buka abilitynya di SharingPolicy::viewTimeline dan
 * CounselingPolicy::viewTimeline.
 *
 * Catatan privasi: `payload` pada case_events memang hanya memuat metadata —
 * tidak pernah transkrip curhat atau catatan klinis. Allowlist di sini lapisan
 * kedua, untuk field yang berupa teks bebas tulisan manusia (`reason`) atau
 * detail yang tidak dibutuhkan peran tersebut (`total_score`, daftar `scopes`).
 */
class CaseTimelineVisibility
{
    /**
     * Kunci payload yang aman untuk peran yang hanya boleh melihat proses,
     * bukan isi. Mengikuti disclaimer di desain dasbor Kepala Sekolah:
     * "Informasi ditampilkan terbatas untuk menjaga privasi siswa".
     *
     * @var array<int, string>
     */
    private const METADATA_PAYLOAD_KEYS = [
        'zone', 'priority', 'sla_hours', 'sla_deadline', 'deadline_at',
        'action', 'type', 'source_type', 'resolution', 'method',
        'scopes_count', 'scheduled_at', 'from', 'to', 'trigger',
        'slot_date', 'slot_time',
    ];

    /**
     * Peran yang punya akses. Nilai `'*'` berarti tanpa penyaringan.
     *
     * @var array<string, array{events: string|array<int, string>, payload: string|array<int, string>}>
     */
    private const RULES = [
        UserRole::HEADTEACHER->value => [
            'events'  => '*',
            'payload' => self::METADATA_PAYLOAD_KEYS,
        ],

        // Belum dibuka. Saat hendak membukanya, tambahkan entri di sini —
        // misalnya counselor dengan 'payload' => '*' karena Guru BK memang
        // sudah berhak melihat isi curhat dan catatan konselingnya sendiri.
    ];

    public static function isAllowed(?string $role): bool
    {
        return $role !== null && array_key_exists($role, self::RULES);
    }

    /**
     * @return array<int, string> Peran yang saat ini punya akses.
     */
    public static function allowedRoles(): array
    {
        return array_keys(self::RULES);
    }

    public static function canSeeEvent(?string $role, CaseEventType $event): bool
    {
        if (! self::isAllowed($role)) {
            return false;
        }

        $allowed = self::RULES[$role]['events'];

        return $allowed === '*' || in_array($event->value, $allowed, true);
    }

    /**
     * Saring payload sesuai peran. Mengembalikan null bila tidak ada field
     * yang tersisa, supaya respons tidak memuat objek kosong.
     *
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    public static function filterPayload(?string $role, ?array $payload): ?array
    {
        if ($payload === null || $payload === [] || ! self::isAllowed($role)) {
            return null;
        }

        $allowed = self::RULES[$role]['payload'];

        if ($allowed === '*') {
            return $payload;
        }

        $filtered = array_intersect_key($payload, array_flip($allowed));

        return $filtered === [] ? null : $filtered;
    }

    /**
     * Apakah peran ini boleh melihat nama aktor di setiap langkah.
     * Desain Kepala Sekolah menampilkan PIC satu kali di kartu identitas,
     * bukan per langkah, jadi nama aktor per baris tidak dikirim.
     */
    public static function canSeeActor(?string $role): bool
    {
        return self::isAllowed($role) && $role !== UserRole::HEADTEACHER->value;
    }
}
