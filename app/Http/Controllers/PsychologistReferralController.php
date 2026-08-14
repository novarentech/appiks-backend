<?php

namespace App\Http\Controllers;

use App\Actions\Psychologist\DecideReferralAction;
use App\Actions\Psychologist\GetPendingReferralsAction;
use App\Http\Requests\DecideReferralRequest;
use App\Http\Resources\BookingScheduleResource;
use App\Models\BookingSchedule;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PsychologistReferralController extends Controller
{
    use ApiResponder;

    /**
     * Get pending incoming referrals.
     *
     * List all pending student booking schedules for the authenticated psychologist.
     */
    #[Group('Psychologist')]
    public function pending(GetPendingReferralsAction $action): JsonResponse
    {
        $profile = auth()->user()->psychologistProfile;
        
        if (!$profile) {
            abort(403, 'Hanya psikolog yang dapat mengakses halaman ini.');
        }

        $referrals = $action->handle($profile);

        return $this->success(BookingScheduleResource::collection($referrals), 'Daftar rujukan masuk berhasil diambil.');
    }

    /**
     * Decide on an incoming referral schedule.
     *
     * Confirm or reject a pending student booking schedule referral.
     */
    #[Group('Psychologist')]
    public function decide(DecideReferralRequest $request, BookingSchedule $booking, DecideReferralAction $action): JsonResponse
    {
        Gate::authorize('decide', $booking);

        $result = $action->handle($booking, $request->validated());

        $message = $request->action === 'confirm' 
            ? 'Rujukan berhasil dikonfirmasi.' 
            : 'Rujukan telah ditolak.';

        return $this->success(new BookingScheduleResource($result), $message);
    }
}
