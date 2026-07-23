<?php

namespace App\Http\Controllers;

use App\Actions\StoreCounselingLogAction;
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
     */
    #[Group('Counseling')]
    public function index(Request $request){
        if (Auth::user()->role != UserRole::STUDENT->value) {
            return $this->error('Only student can access this endpoint',403);
        }
        $counselings = Counseling::with(['student','counselor','sharing'])->where('student_id',Auth::user()->id)->get();
        return $this->success(CounselingResource::collection($counselings));
    }
    /**
     * Create new counseling
     *
     * Membuat sebuah sesi konseling baru baik internal (guru) maupun external (psikologi)
     */
    #[Group('Counseling')]
    public function store(CreateCounselingRequest $request){
        $payload = $request->except('date','time');
        // return $this->success($payload);
        $counseling = Counseling::create($payload);
        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Get counseling detail
     *
     * Melihat detail sebuah sesi konseling
     */
    #[Group('Counseling')]
    public function show(Counseling $counseling){
        $counseling->load(['student','counselor']);
        return $this->success(new CounselingResource($counseling));
    }
    
    /**
     * Acknowledge counseling request
     *
     * Menyetujui jadwal permintaan konseling (pov siswa)
     */
    #[Group('Counseling')]
    public function acknowledge(Request $request, Counseling $counseling){
        $request->validate([
            'type' => 'required|string|in:accept,decline'
        ]);
        if ($request->type === 'accept'){
            $counseling->update([
                'status' => CounselingStatus::DIJADWALKAN->value
            ]);
            $counseling->sharing->update(['status'=>ReportStatus::DIJADWALKAN->value]);
        }else{
            $counseling->update([
                'status' => CounselingStatus::DITOLAK->value
            ]);
            $counseling->sharing->update(['status'=>ReportStatus::DITOLAK->value]);
        }
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
