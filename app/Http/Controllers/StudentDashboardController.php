<?php

namespace App\Http\Controllers;

use App\Enums\ConsentStatus;
use App\Enums\CounselingStatus;
use App\Models\Counseling;
use App\Models\CounselingConsent;
use App\Traits\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentDashboardController extends Controller
{
    use ApiResponder;

    /**
     * Supply homepage summary statistics/widgets for the student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWidgets(Request $request): JsonResponse
    {
        $studentId = auth()->id();

        $activeCount = Counseling::where('student_id', $studentId)
            ->whereIn('status', [CounselingStatus::DIJADWALKAN, CounselingStatus::MENUNGGU])
            ->count();

        $completedCount = Counseling::where('student_id', $studentId)
            ->where('status', CounselingStatus::SELESAI)
            ->count();

        $pendingConsentsCount = CounselingConsent::whereHas('counseling', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->where('status', ConsentStatus::PENDING)
            ->count();

        return $this->success([
            'active_counselings_count' => $activeCount,
            'completed_counselings_count' => $completedCount,
            'pending_consents_count' => $pendingConsentsCount,
        ], 'Student widget summary statistics retrieved.');
    }

    /**
     * Supplies data for the Activity Center tab (student's counselings).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCounselings(Request $request): JsonResponse
    {
        $studentId = auth()->id();

        $query = Counseling::where('student_id', $studentId)
            ->with([
                'counselor',
                'psychologist.psychologistProfile',
                'latestConsent'
            ])
            ->latest();

        if ($request->has('page') || $request->has('search')) {
            $counselings = $query->paginate($request->input('per_page', 10));
        } else {
            $counselings = $query->get();
        }

        return $this->success($counselings, 'Student counseling list retrieved.');
    }
}
