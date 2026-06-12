<?php

namespace App\Http\Controllers;

use App\Actions\UpdateConsentAction;
use App\Http\Requests\UpdateConsentRequest;
use App\Models\Counseling;
use App\Models\CounselingConsent;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class StudentConsentController extends Controller
{
    use ApiResponder;

    /**
     * Show the latest/active pending consent request for a counseling session.
     *
     * @param Counseling $counseling
     * @return JsonResponse
     */
    public function show(Counseling $counseling): JsonResponse
    {
        // Authorize view via CounselingPolicy viewStudent method
        Gate::authorize('viewStudent', $counseling);

        $consent = $counseling->latestConsent;

        if (!$consent) {
            return $this->error('No consent requests found for this counseling session.', 404);
        }

        return $this->success($consent, 'Active consent request details retrieved.');
    }

    /**
     * Update/submit granular consent response.
     *
     * @param UpdateConsentRequest $request
     * @param CounselingConsent $consent
     * @param UpdateConsentAction $action
     * @return JsonResponse
     */
    public function update(
        UpdateConsentRequest $request,
        CounselingConsent $consent,
        UpdateConsentAction $action
    ): JsonResponse {
        // Authorize update via CounselingConsentPolicy
        Gate::authorize('update', $consent);

        $isGranted = (bool) $request->input('is_granted');
        $scopes = $request->input('scopes', []);

        $updatedConsent = $action->execute($consent, $isGranted, $scopes);

        return $this->success($updatedConsent, 'Digital consent submission recorded successfully.');
    }
}
