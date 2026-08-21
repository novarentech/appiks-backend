<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\StoreClinicalSummaryFeedbackRequest;
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
     * Mendapatkan ringkasan klinis rujukan yang dihasilkan oleh AI beserta identitas siswa, guru BK penanggung jawab, curhat terkait dan analisis NLP, catatan klinis, dan masukan evaluasi.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     student: array{
     *       name: string,
     *       nis: string,
     *       class: string|null,
     *       counselor_name: string|null,
     *       status: string,
     *       priority: string,
     *       deadline_at: string,
     *       reported_at: string,
     *     },
     *     sharing: array{
     *       id: int,
     *       title: string,
     *       description: string,
     *       priority: string,
     *       status: string,
     *       created_at: string,
     *       nlp: array{
     *         id: int,
     *         flag: string|null,
     *         status: string|null,
     *         response: array<string, mixed>|null
     *       }|null
     *     }|null,
     *     generated_at: string,
     *     llm_provider: string,
     *     summary_text: string,
     *     raw_payload: array<string, mixed>,
     *     clinical_notes: string|null,
     *     rating: string|null,
     *     improvement_feedback: string|null
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

        // Load related relationships
        $counseling->load(['sharing.nlp', 'student.room', 'student.counselor', 'counselor']);

        // De-anonymized identity
        $student = $booking->counseling->student;
        $studentIdentity = [
            'name'           => $student->name,
            'nis'            => $student->username,
            'class'          => $student->room->name ?? null,
            'counselor_name' => $student->counselor->name ?? $counseling->counselor->name ?? null,
            'status'         => $booking->status,
            'priority'       => $counseling->sharing->priority,
            'deadline_at'    => $booking->deadline_at,
            'reported_at'    => $counseling->created_at,
        ];

        // Related sharing with NLP analysis
        $sharingData = null;
        if ($counseling->sharing) {
            $sharing = $counseling->sharing;
            $sharingData = [
                'id'          => $sharing->id,
                'title'       => $sharing->title,
                'description' => $sharing->description,
                'priority'    => $sharing->priority,
                'status'      => $sharing->status,
                'created_at'  => $sharing->created_at,
                'nlp'         => $sharing->nlp ? [
                    'id'       => $sharing->nlp->id,
                    'flag'     => $sharing->nlp->flag,
                    'status'   => $sharing->nlp->status?->value ?? $sharing->nlp->status,
                    'response' => $sharing->nlp->response,
                ] : null,
            ];
        }

        return $this->success([
            'student'              => $studentIdentity,
            'sharing'              => $sharingData,
            'generated_at'         => $summary->updated_at,
            'llm_provider'         => 'gemini-2.5-flash',
            'summary_text'         => $summary->summary_data,
            'raw_payload'          => $summary->raw_payload,
            'clinical_notes'       => $summary->clinical_notes,
            'rating'               => $summary->rating,
            'improvement_feedback' => $summary->improvement_feedback,
        ], 'Ringkasan berhasil diambil.');
    }

    /**
     * Save clinical notes and AI feedback
     *
     * Menyimpan catatan klinis psikolog, penilaian kualitas ringkasan AI (good/bad), dan masukan perbaikan.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     clinical_notes: string|null,
     *     rating: string|null,
     *     improvement_feedback: string|null,
     *     updated_at: string
     *   }
     * }
     */
    #[Group('Psychologist')]
    public function storeFeedback(StoreClinicalSummaryFeedbackRequest $request, Counseling $counseling): JsonResponse
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

        $summary = ClinicalSummary::firstOrCreate(
            ['counseling_id' => $counseling->id],
            ['summary_data' => '']
        );

        $summary->update($request->only([
            'clinical_notes',
            'rating',
            'improvement_feedback',
        ]));

        return $this->success([
            'clinical_notes'       => $summary->clinical_notes,
            'rating'               => $summary->rating,
            'improvement_feedback' => $summary->improvement_feedback,
            'updated_at'           => $summary->updated_at,
        ], 'Catatan klinis dan masukan berhasil disimpan.');
    }
}
