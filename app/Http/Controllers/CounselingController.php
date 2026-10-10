<?php

namespace App\Http\Controllers;

use App\Actions\StoreCounselingLogAction;
use App\Enums\BookingStatus;
use App\Enums\CounselingMethod;
use App\Enums\CounselingResolution;
use App\Enums\ConsentStatus;
use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Requests\CreateCounselingRequest;
use App\Http\Resources\CounselingResource;
use App\Models\Counseling;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CounselingController extends Controller
{
    use ApiResponder;

    /**
     * Get all counseling
     *
     * Mendapatkan semua data sesi konseling (khusus siswa)
     * 
     * @param  string  $type  internal | external
     */
    #[Group('Counseling')]
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:internal,external'
        ]);
        $type = $request->filled('type') ? $request->type : 'internal';
        if (Auth::user()->role != UserRole::STUDENT->value) {
            return $this->error('Only student can access this endpoint', 403);
        }
        $counselings = Counseling::with(['student', 'counselor', 'sharing', 'psychologist', 'psychologist.psychologistProfile', 'latestBookingSchedule.slot', 'clinicalSummary'])
            ->withCount(['bookingSchedule as rescheduled_bookings_count' => function ($query) {
                $query->where('status', BookingStatus::RESCHEDULED->value);
            }])
            ->where('student_id', Auth::id())
            ->where('type', $type)
            ->get();
        return $this->success(CounselingResource::collection($counselings));
    }
    /**
     * Create new counseling
     *
     * Membuat sebuah sesi konseling baru baik internal (guru) maupun external (psikologi)
     */
    #[Group('Counseling')]
    public function store(CreateCounselingRequest $request)
    {
        $payload = $request->except('date', 'time');
        // return $this->success($payload);
        $counseling = Counseling::create($payload);
        // CounselingObserver sudah memindahkan status sharing; baris ini dipertahankan
        // sebagai jaring agar responsnya sudah membawa status terbaru.
        $counseling->sharing?->update([
            'status'=>ReportStatus::MENUNGGU_PERSETUJUAN->value
        ]);
        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Get counseling detail
     *
     * Melihat detail sebuah sesi konseling
     */
    #[Group('Counseling')]
    public function show(Counseling $counseling)
    {
        $counseling->load(['student', 'counselor', 'latestBookingSchedule.slot', 'bookingSchedule']);
        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Acknowledge counseling request
     *
     * Menyetujui jadwal permintaan konseling (pov siswa)
     */
    #[Group('Counseling')]
    public function acknowledge(Request $request, Counseling $counseling)
    {
        Gate::authorize('acknowledge', $counseling);

        $request->validate([
            'type' => 'required|string|in:accept,decline'
        ]);

        if (! $counseling->status->needsStudentAction()) {
            return $this->error('Jadwal konseling ini tidak sedang menunggu persetujuan Anda.', 422);
        }

        if ($request->type === 'accept') {
            $counseling->update([
                'status' => CounselingStatus::DIJADWALKAN->value
            ]);
            $counseling->sharing?->update(['status' => ReportStatus::DIJADWALKAN->value]);
        } else {
            $counseling->update([
                'status' => CounselingStatus::DITOLAK->value
            ]);
            $counseling->sharing?->update(['status' => ReportStatus::DITOLAK->value]);
        }
        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Cancel counseling session
     *
     * Membatalkan sesi konseling. Hanya Guru BK yang ditugaskan pada sesi itu.
     */
    #[Group('Counseling')]
    public function cancel(Request $request, Counseling $counseling)
    {
        Gate::authorize('manageSchedule', $counseling);

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if ($counseling->status->isTerminal()) {
            return $this->error('Sesi konseling ini sudah ditutup.', 422);
        }

        $counseling->update([
            'status' => CounselingStatus::DIBATALKAN->value,
            'notes'  => $validated['notes'] ?? $counseling->notes,
        ]);
        $counseling->sharing?->update(['status' => ReportStatus::DIBATALKAN->value]);

        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Re-propose counseling schedule
     *
     * Mengajukan ulang jadwal setelah siswa menolak jadwal sebelumnya.
     * Hanya Guru BK yang ditugaskan pada sesi itu.
     */
    #[Group('Counseling')]
    public function repropose(Request $request, Counseling $counseling)
    {
        Gate::authorize('manageSchedule', $counseling);

        $validated = $request->validate([
            'date'  => 'required|date_format:Y-m-d',
            'time'  => 'required|date_format:H:i',
            'room'  => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($counseling->status !== CounselingStatus::DITOLAK) {
            return $this->error('Jadwal hanya dapat diajukan ulang setelah ditolak siswa.', 422);
        }

        $counseling->update([
            'status'       => CounselingStatus::DIJADWAL_ULANG->value,
            'scheduled_at' => $validated['date'].' '.$validated['time'],
            'room'         => $validated['room'] ?? $counseling->room,
            'notes'        => $validated['notes'] ?? $counseling->notes,
        ]);
        $counseling->sharing?->update(['status' => ReportStatus::MENUNGGU_PERSETUJUAN->value]);

        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Store counseling clinical log outcome
     */
    #[Group('Counseling')]
    public function storeLog(Request $request, StoreCounselingLogAction $action)
    {
        $validated = $request->validate([
            'counseling_id' => 'required|integer|exists:counselings,id',
            'session_mode' => ['required', 'string', Rule::enum(CounselingMethod::class)],
            'clinical_notes' => 'required|string',
            'resolution_status' => ['required', 'string', Rule::enum(CounselingResolution::class)],
        ]);

        $counseling = Counseling::findOrFail($validated['counseling_id']);
        Gate::authorize('storeLog', $counseling);

        $log = $action->handle($validated);

        return $this->success($log);
    }

    /**
     * Re-send or manually create a digital consent request for a counseling session.
     */
    #[Group('Counseling')]
    public function sendConsent(Counseling $counseling): JsonResponse
    {
        // Only the counselor assigned to this session is authorized
        Gate::authorize('storeLog', $counseling);

        $consent = $counseling->consents()->create([
            'status' => ConsentStatus::PENDING,
        ]);

        return $this->created($consent, 'Digital consent request initiated successfully.');
    }
}
