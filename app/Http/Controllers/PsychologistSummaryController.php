<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\BookingSchedule;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

class PsychologistSummaryController extends Controller
{
    use ApiResponder;

    /**
     * Get AI referral clinical summary
     *
     * Mendapatkan ringkasan klinis rujukan yang dihasilkan oleh AI beserta identitas siswa dan payload mentah.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     student: array{
     *       name: string,
     *       nis: string,
     *       class: string|null
     *     },
     *     generated_at: string,
     *     llm_provider: string,
     *     summary_text: string,
     *     raw_payload: array<string, mixed>
     *   }
     * }
     */
    #[Group('Psychologist')]
    public function getSummary(Counseling $counseling): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;
        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        // Authorize: Ensure the psychologist owns a confirmed booking for this counseling referral
        $booking = BookingSchedule::where('counseling_id', $counseling->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereHas('slot', function ($q) use ($profile) {
                $q->where('psychologist_id', $profile->id);
            })->first();

        if (!$booking) {
            abort(403, 'Akses ditolak. Anda tidak memiliki rujukan aktif untuk sesi ini.');
        }

        // Fetch AI Clinical Summary
        $summary = ClinicalSummary::where('counseling_id', $counseling->id)->first();
        if (!$summary) {
            return $this->error('Ringkasan AI belum tersedia atau gagal dibuat.', 404);
        }

        // De-anonymized identity
        $student = $booking->counseling->student;
        $studentIdentity = [
            'name'  => $student->name,
            'nis'   => $student->username,
            'class' => $student->room->name ?? null,
        ];

        return $this->success([
            'student'      => $studentIdentity,
            'generated_at' => $summary->updated_at,
            'llm_provider' => 'gemini-2.5-flash',
            'summary_text' => $summary->summary_data,
            'raw_payload'  => $summary->raw_payload,
        ], 'Ringkasan berhasil diambil.');
    }
}
