<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\MoodStatus;
use App\Actions\BuildMoodRecapAction;
use App\Http\Requests\StoreClinicalSummaryFeedbackRequest;
use App\Http\Resources\CounselingResource;
use App\Http\Resources\MoodRecordResource;
use App\Http\Resources\SharingResource;
use App\Models\BookingSchedule;
use App\Models\ClinicalSummary;
use App\Models\Counseling;
use App\Models\MoodRecord;
use App\Models\Sharing;
use App\Traits\ApiResponder;
use Carbon\Carbon;
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
            'llm_provider'         => 'gemini-3.1-flash-lite',
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

    /**
     * Get a student's mood recap for the latest 30 calendar days.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     student: array{
     *       id: int,
     *       name: string,
     *       nis: string,
     *       class: string|null
     *     },
     *     recap: array{happy: int, angry: int, sad: int, neutral: int},
     *     mean: "secure"|"insecure",
     *     secure: int,
     *     insecure: int,
     *     moods: array<array{recorded: string, status: string}>
     *   }
     * }
     */
    #[Group('Mood Record')]
    public function getSharingMonthlyRecap(
        Counseling $counseling,
        BuildMoodRecapAction $recapAction
    ): JsonResponse {
        $counseling->load('student.room');
        $student = $counseling->student;
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(29);

        $mood = MoodRecord::where('user_id', $counseling->student_id)
            ->whereBetween('recorded', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('recorded')
            ->get();

        ['recap' => $recap, 'mean' => $mean, 'secure' => $secure, 'insecure' => $insecure] = $recapAction->handle($mood);

        return $this->success([
            'student' => [
                'id'    => $student->id,
                'name'  => $student->name,
                'nis'   => $student->username,
                'class' => $student->room?->name,
            ],
            'recap' => [
                MoodStatus::HAPPY->value   => (int) ($recap[MoodStatus::HAPPY->value] ?? 0),
                MoodStatus::ANGRY->value   => (int) ($recap[MoodStatus::ANGRY->value] ?? 0),
                MoodStatus::SAD->value     => (int) ($recap[MoodStatus::SAD->value] ?? 0),
                MoodStatus::NEUTRAL->value => (int) ($recap[MoodStatus::NEUTRAL->value] ?? 0),
            ],
            'mean' => $mean,
            'secure' => $secure,
            'insecure' => $insecure,
            'moods' => MoodRecordResource::collection($mood),
        ]);
    }

    /**
     * Get a student's sharing records for the latest 30 calendar days.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     student: array{
     *       id: int,
     *       name: string,
     *       nis: string,
     *       class: string|null
     *     },
     *     sharings: array<array<string, mixed>>
     *   }
     * }
     */
    #[Group('Sharing')]
    public function getStudentSharing(Counseling $counseling): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;
        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        $hasBooking = BookingSchedule::where('counseling_id', $counseling->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereHas('slot', function ($query) use ($profile) {
                $query->where('psychologist_id', $profile->id);
            })
            ->exists();

        if (!$hasBooking) {
            abort(403, 'Akses ditolak. Anda tidak memiliki rujukan aktif untuk sesi ini.');
        }

        $counseling->load('student.room');
        $student = $counseling->student;
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays(29);

        $sharings = Sharing::where('user_id', $counseling->student_id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with(['nlp', 'counseling'])
            ->latest()
            ->get();

        return $this->success([
            'student' => [
                'id'    => $student->id,
                'name'  => $student->name,
                'nis'   => $student->username,
                'class' => $student->room?->name,
            ],
            'sharings' => SharingResource::collection($sharings),
        ], 'Student sharing records retrieved.');
    }

    /**
     * Get the latest counseling for the student with an assigned counselor.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array<string, mixed>
     * }
     */
    #[Group('Counseling')]
    public function getLatestCounseling(Counseling $counseling): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;
        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        $hasBooking = BookingSchedule::where('counseling_id', $counseling->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereHas('slot', function ($query) use ($profile) {
                $query->where('psychologist_id', $profile->id);
            })
            ->exists();

        if (!$hasBooking) {
            abort(403, 'Akses ditolak. Anda tidak memiliki rujukan aktif untuk sesi ini.');
        }

        $latestCounseling = Counseling::where('student_id', $counseling->student_id)
            ->whereNotNull('counselor_id')
            ->with(['student.room', 'counselor', 'sharing', 'psychologist'])
            ->latest()
            ->first();

        if (!$latestCounseling) {
            return $this->error('No counseling with an assigned counselor found.', 404);
        }

        return $this->success(
            new CounselingResource($latestCounseling),
            'Latest counseling retrieved.'
        );
    }
}
